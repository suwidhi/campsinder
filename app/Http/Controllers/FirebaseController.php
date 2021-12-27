<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\Auth\Token\Exception\InvalidToken;
use Illuminate\Support\Facades\Session;
use Illuminate\Session\SessionManager;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Factory;
use PhpParser\Node\Stmt\Break_;

class FirebaseController extends Controller
{
    protected $auth, $database;

    public function __construct()
    {
        $factory = (new Factory)
        ->withServiceAccount(__DIR__ . '/../Fireabase/firebase-config.json');

        $this->auth = $factory->createAuth();
    }

    public function signUp()
    {
        $email = ''; $password = ''; // anggap dari req body

        try {
            $newUser = $this->auth->createUserWithEmailAndPassword($email, $password);
            dd($newUser);
        } catch(\Throwable $e) {
            dd($e->getMessage());
        }
    }

    public function signIn()
    {
        $email = '';
        $password = '';

        try {
            $signInResult = $this->auth->signInWithEmailAndPassword($email, $password);
            
            // sesuatu yang berhubungan dengan session
            Session::put('firebaseUserId', $signInResult->firebaseUserId());
            Session::put('idToken', $signInResult->idToken());
            Session::save();
            // mungkin perlu save? who knows?
            dd($signInResult);
        } catch(\Throwable $e) {
            switch($e->getMessage()) {
                case 'INVALID_PASSWORD':
                    dd('Your username or password is invalid.');
                    break;

                case 'EMAIL_NOT_FOUND':
                    dd('Your username did not exists.');
                    break;

                default:
                    dd($e->getMessage());
            }
        }
    }

    public function signOut() 
    {
        if (Session::has('firebaseUserId') && Session::has('idToken')) {
            $this->auth->revokeRefreshTokens(Session::get('firebaseUserId'));
            Session::forget('fireabaseUserId');
            Session::forget('idToken');
            Session::save();
            dd("user berhasil logout");
        } else {
            dd('user belum login');
        }

    }

    public function userCheck()
    {
        $idToken = "";

        try {
            $verifiedToken = $this->auth->verifyIdToken(Session::get('idToken'), $checkIfRevoked = true );
        } catch(\Throwable $e) {

        }
    }

}
