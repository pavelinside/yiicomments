<?php
namespace app\modules\online\services;

/**
 * функции из Вестника
 */
class LoginService {
    public function init(){
        ini_set('session.save_path', 'some_path');
        ini_set('session.gc_maxlifetime', 1440);
        session_start();
    }

    /**
     * login
     * @param string $login
     * @param string $password
     */
    public function authorize($login, $password){
        $qry = "SELECT id FROM usr WHERE login='$login' AND pwd='" . md5(trim($password)) . "'";
        $usrid = \db::val($qry);
        if($usrid){
            $_SESSION['id'] = $usrid;
        }
        return $usrid;
    }


    public function isAuthorize(){
        return array_key_exists('id', $_SESSION) ? $_SESSION['id'] : false;
    }

    /**
     * start session
     * // !!! if (isset($_REQUEST[session_name()])) session_start();- только если пришла cookie
     * @param boolean $isUserActivity
     * @return boolean
     */
    public function sessionStart($isUserActivity = true, $prefix = null){
        if(session_id()){
            return true;
        }
        session_name('encoder');

        $sessionLifetime = 300;
        ini_set('session.cookie_lifetime', $sessionLifetime);
        if ($sessionLifetime) {
            ini_set('session.gc_maxlifetime', $sessionLifetime);
        }
        if(!session_start()){
            // TODO error 403
            return false;
        }

        $t = time();

        // check session lastactivity, if a user activity - set current time
        if($sessionLifetime){
            if(isset($_SESSION['lastactivity']) && $t - $_SESSION['lastactivity'] >= $sessionLifetime){
                $this->sessionDestroy();
                // TODO error 401
                return false;
            }else{
                if($isUserActivity){
                    $_SESSION['lastactivity'] = $t;
                }
            }
        }

        setcookie(session_name(), session_id(), time()+$sessionLifetime);

        // every lifetime cookie value of session need change
        $idLifetime = 60;
        if($idLifetime){
            if(isset($_SESSION['starttime'])){
                if($t - $_SESSION['starttime'] >= $idLifetime){
                    session_regenerate_id(true);
                    $_SESSION['starttime'] = $t;
                }
            }else{
                $_SESSION['starttime'] = $t;
            }
        }

        return true;
    }

    /**
     * quit, delete session
     */
    public function sessionDestroy(){
        if(session_id()){
            session_unset();
            // remove cookie
            setcookie(session_name(), session_id(), time() - 60 * 60 * 24);
            session_destroy();
        }
    }
}