<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the posts for the user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the posts liked by the user.
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'likes')->using(Like::class)->withTimestamps()->withPivot('reaction');
    }


    //NOUVEAU

    //polls créé par usr
    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }


    // pas use (déjà comme ca dans poll_votes comme on a colonne user_id (1 usr -> peut avoir plsr lignes de la table)
    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
