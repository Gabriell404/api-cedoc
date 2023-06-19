<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google2fa_secret',
        'last_login',
        'ip_login'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected function google2fasecret()
    {
        return new Attribute(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }

    public function loginSecurity()
    {
        return $this->hasOne(LoginSecurity::class);
    }

    public function perfils()
    {
        return $this->belongsToMany(Perfil::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function adicionaPerfil($perfil){

        if (is_string($perfil)) {
            return $this->perfils()->save(
                Perfil::where('nome', '=', $perfil)->firstOrFail()

            );
        }
        return $this->perfils()->save(
            Perfil::where('nome', '=', $perfil->nome)->firstOrFail()
        );
    }

    public function removePerfil($perfil){
        if (is_string($perfil)) {
            return $this->perfils()->detach(
                Perfil::where('nome', '=', $perfil)->firstOrFail()

            );
        }
        return $this->perfils()->detach(
            Perfil::where('nome', '=', $perfil->nome)->firstOrFail()

        );
    }

    public function existePerfil($perfil){

        if (is_string($perfil)) {
            return $this->perfils->contains('nome', $perfil);
        }

        return $perfil->intersect($this->perfils)->count();

    }

    public static function existePermissao(int|string $permissao, int|string $perfil){

        if (DB::table('perfil_role')->where('role_id', $permissao)->where('perfil_id', $perfil)->count()) {

            return true;

        }else{
            return false;
        }

    }

    public function existeAdmin()
    {
        return $this->existePerfil('Master');
    }

}
