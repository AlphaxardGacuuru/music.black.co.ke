<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $userRoles = [
            "userId" => $this->id,
            "roles" => $this->roles,
        ];

        $userRoleNames = [
            "userId" => $this->id,
            "roleNames" => $this->getRoleNames(),
        ];

        $viewerId = auth('sanctum')->id();

        return [
            "id" => $this->id,
            "name" => $this->name,
            "username" => $this->username,
            "email" => $this->email,
            "phone" => $this->phone,
            "gender" => $this->gender,
            "avatar" => $this->avatar,
            "backdrop" => $this->backdrop,
            "dob" => $this->dob,
            "bio" => $this->bio,
            "location" => $this->location,
            "withdrawal" => $this->withdrawal,
            "posts" => $this->posts()->count(),
            "following" => $this->follows()->count() - 1,
            "fans" => $this->fans(),
            "hasFollowed" => $this->hasFollowed($viewerId),
            "hasBought1" => $this->hasBought1($viewerId),
            "decos" => $this->decos,
            "balance" => $this->balance(),
            "videos" => $this->videos()->count(),
            "boughtVideos" => $this->boughtVideos()->count(),
            "audios" => $this->audios()->count(),
            "boughtAudios" => $this->boughtAudios()->count(),
            "verified" => (bool) $this->verified,
            "accountType" => $this->account_type,
            "emailVerifiedAt" => $this->email_verified_at,
            "settings" => $this->settings,
            "roles" => [$userRoles],
            "roleNames" => [$userRoleNames],
            "permissions" => $this->getAllPermissions()->pluck('name')->unique()->values(),
            "twoFactorEnabled" => $this->hasTwoFactorEnabled(),
            "referralsCount" => $this->referrals_made_count ?? $this->referralsMade()->count(),
            "referredBy" => $this->whenLoaded('referral', fn() => $this->referral?->referrer
                ? ["id" => $this->referral->referrer->id, "name" => $this->referral->referrer->name]
                : null),
            "pushSubscriptionsCount" => $this->push_subscriptions_count ?? $this->pushSubscriptions()->count(),
            "createdAt" => $this->created_at,
        ];
    }
}
