<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\RegisterRequest;
use App\Http\Requests\Api\User\UpdateProfileRequest;
use App\Repositories\Api\Access\User\UserRepository;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Lcobucci\JWT\Parser;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class UserController
 */
class UserController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $users;


    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @param RegisterRequest $request
     * @return mixed
     */
    public function register(RegisterRequest $request)
    {

        $newUser = $this->users->create($request->all());

        if (!$newUser)
            throw new StoreResourceFailedException('Could not create new user.');

        return $newUser;
    }


    /**
     * @param Request $request
     */
    public function logout(Request $request) {

        //Revoke access token
        $value = $request->bearerToken();
        $id= (new Parser())->parse($value)->getHeader('jti');
        $token = $request->user()->tokens->find($id);
        $token->revoke();
    }

    /**
     * @param Request $request
     * @param $args
     * @return array
     */
    public function getProfile(Request $request, $args){

        $result = [];
        $args = explode(",",$args);
        foreach($args as $value ){
            if($value == 'name')
                $result['name'] = $request->user()->name;

            elseif($value == 'email')
                $result['email'] = $request->user()->email;

            elseif($value == 'photo')
                $result['photo'] = $request->user()->photo;

            else {
                throw new BadRequestHttpException("Unknown request keyword");
            }
        }

        return $result;
    }

    /**
     * @param UpdateProfileRequest $request
     * @return array
     */
    public function updateProfile(UpdateProfileRequest $request) {

        if($request->photo)
            $photo = $request->file('photo')->store('profile_pic');

        $response = $request->user()->update([
            'name' => $request->name ?? $request->user()->name,
            'email' => $request->email ?? $request->user()->email,
            'photo' => $photo ?? $request->user()->photo,
        ]);

        if ($response) {
            $result = [];
            $result['name'] = $request->user()->name;
            $result['email'] = $request->user()->email;
            $result['photo'] = $request->user()->photo;
            return $result;
        } else
            throw new UpdateResourceFailedException('Could not update user.');
    }

}
