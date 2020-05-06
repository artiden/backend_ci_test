<?php

/**
 * Created by PhpStorm.
 * User: mr.incognito
 * Date: 10.11.2018
 * Time: 21:36
 */
class Main_page extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

        App::get_ci()->load->model('User_model');
        App::get_ci()->load->model('Login_model');
        App::get_ci()->load->model('Post_model');

        if (is_prod())
        {
            die('In production it will be hard to debug! Run as development environment!');
        }
    }

    public function index()
    {
        $user = User_model::get_user();



        App::get_ci()->load->view('main_page', ['user' => User_model::preparation($user, 'default')]);
    }

    public function get_all_posts()
    {
        $posts =  Post_model::preparation(Post_model::get_all(), 'main_page');
        return $this->response_success(['posts' => $posts]);
    }

    public function get_post($post_id){ // or can be $this->input->post('news_id') , but better for GET REQUEST USE THIS

        $post_id = intval($post_id);

        if (empty($post_id)){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_WRONG_PARAMS);
        }

        try
        {
            $post = new Post_model($post_id);
        } catch (EmeraldModelNoDataException $ex){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NO_DATA);
        }


        $posts =  Post_model::preparation($post, 'full_info');
        return $this->response_success(['post' => $posts]);
    }


    public function comment()
    { // or can be App::get_ci()->input->post('news_id') , but better for GET REQUEST USE THIS ( tests )
        $token = $this->input->get_post('token');
        if (!User_model::is_logged($token)) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NEED_AUTH);
        }

        $post_id = intval($this->input->get_post('post_id'));
        $message = $this->input->get_post('message');

        if (empty($post_id) || empty($message)){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_WRONG_PARAMS);
        }

        try
        {
            $post = new Post_model($post_id);
        } catch (EmeraldModelNoDataException $ex){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NO_DATA);
        }

        $user = User_model::authToken($token);
        // Честно говоря, это сделал бы через репозиторий.
        if (!Comment_model::add($user->get_id(), $post_id, $message)) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_TRY_LATER);
        }

        $posts =  Post_model::preparation($post, 'full_info');
        return $this->response_success(['post' => $posts]);
    }
    
    // В следующих 2-х методах хорошо бы сделать по ресту. типа post/1/like и comment/x/like. + не плохо бы подошла бы штука, как Polymorphic Relationships. Но это если нужно лайки вынести в отдельную таблицу и сохранять время, юзера, etc...
    public function like_post()
    {
        $token = $this->input->get_post('token');

        if (!User_model::is_logged($token)) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NEED_AUTH);
        }

        $user = User_model::authToken($token);
        if ($user->haveNoLikes()) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_TRY_LATER);
        }

        $postId = $this->input->get_post('post_id');
        try {
            $post = new Post_model($postId);
        } catch (EmeraldModelNoDataException $e) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NO_DATA);
        }

        if (!Post_model::like($postId)) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_TRY_LATER);
        }
        $user->set_likes($user->get_likes() - 1);

        $posts =  Post_model::preparation($post, 'full_info');
        return $this->response_success([
            'post' => $posts,
        ]);
    }

    public function like_comment()
    {
        //Все аналогично с предыдущим методом, только моделька другая.
    }

    public function login()
    {
        $email = $this->input->get('email');
        $password = $this->input->get('password');

        if (is_null($email) || is_null($password)) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_REQUIRED_DATA_NOT_PROVIDED);
        }

        try {
            /**
             * @var User_model $user
             */
            $user = User_model::login($email, $password);
        } catch (Exception $e) {
            return $this->response_error($e->getMessage());
        }

        $userId = $user->get_id();
        Login_model::start_session($userId);

        return $this->response_success([
            'user_id' => $userId,
            'token' => $user->get_token(),
        ]);
    }


    public function logout()
    {
        Login_model::logout();
        redirect(site_url('/'));
    }

    public function add_money()
    {
        $userId = $this->input->get_post('user_id');
        $amount = floatval($this->input->get_post('amount'));
        User_model::addMoney($userId, $amount);
        return $this->response_success([
            'amount' => $amount,
        ]);
    }

    public function buy_boosterpack(){
        // todo: add money to user logic
        return $this->response_success(['amount' => rand(1,55)]);
    }


    public function like(){
        // todo: add like post\comment logic
        return $this->response_success(['likes' => rand(1,55)]); // Колво лайков под постом \ комментарием чтобы обновить
    }

}
