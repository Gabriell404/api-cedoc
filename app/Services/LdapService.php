<?php
namespace App\Services;

use Exception;
use LdapRecord\Support\Arr;

Class LdapService{

  /**
   * Default Responses.
   *
   * @return void
   */

    public static function connect($user = null, $password = null)
    {
        try {

        $connection = new \LdapRecord\Connection([
            'hosts' => ['10.54.56.8'],
            'base_dn'=> 'DC=aracoop,DC=local',
            'username' => 'ARACOOP\Administrator',
            'password' => 'Psw@4264#ti',
            'port' => 389,
        ]);

        $connection->connect();

        $result = $connection->query()->where('samaccountname', '=', $user)->firstOrFail();

        $nameExplode = explode(' ', $result['name'][0]);
        $firtsName = Arr::first($nameExplode);
        $lastName = Arr::last($nameExplode);

        // return ;
        if($connection->auth()->attempt($result['distinguishedname'][0], $password)){
            return [
                'user' => $firtsName.' '.$lastName,
                'connect' => true,
                'description' => $result['description'][0]
            ];
        }else{
            return false;
        }

        } catch (\LdapRecord\Auth\BindException $e) {
            $error = $e->getDetailedError()->getDiagnosticMessage();

            if (strpos($error, '532') !== false) {
                return 'Sua senha está expirada.';
            } elseif (strpos($error, '533') !== false) {
                return 'Sua conta está desabilitada.';
            } elseif (strpos($error, '701') !== false) {
                return 'Sua conta está expirada.';
            } elseif (strpos($error, '775') !== false) {
                return 'Sua conta está bloqueada.';
            }

            return 'Usuário ou senhas incorretos.';
        }

    }

}
