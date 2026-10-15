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
        $viewer = auth('sanctum')->user();
        $viewerId = $viewer?->id;

        // Profiles, charts and comments are public. Contact details, the
        // wallet and account settings are only for the user and the admin.
        $isPrivateViewer = $viewer !== null
            && ((string) $viewer->id === (string) $this->id
                || $viewer->email === config('admin.email'));

        return [
            "id" => $this->id,
            "name" => $this->name,
            "username" => $this->username,
            "gender" => $this->gender,
            "avatar" => $this->avatar,
            "backdrop" => $this->backdrop,
            "bio" => $this->bio,
            "location" => $this->location,
            "posts" => $this->posts()->count(),
            "following" => $this->follows()->count() - 1,
            "fans" => $this->fans(),
            "hasFollowed" => $this->hasFollowed($viewerId),
            "hasBought1" => $this->hasBought1($viewerId),
            "decos" => $this->decos,
            "videos" => $this->videos()->count(),
            "audios" => $this->audios()->count(),
            "verified" => (bool) $this->verified,
            "accountType" => $this->account_type,
            "createdAt" => $this->created_at,
            $this->mergeWhen($isPrivateViewer, fn() => [
                "email" => $this->email,
                "phone" => $this->phone,
                "dob" => $this->dob,
                "withdrawal" => $this->withdrawal,
                "balance" => $this->balance(),
                "boughtVideos" => $this->boughtVideos()->count(),
                "boughtAudios" => $this->boughtAudios()->count(),
                "emailVerifiedAt" => $this->email_verified_at,
                "settings" => $this->settings,
                "roles" => [["userId" => $this->id, "roles" => $this->roles]],
                "roleNames" => [["userId" => $this->id, "roleNames" => $this->getRoleNames()]],
                "permissions" => $this->getAllPermissions()->pluck('name')->unique()->values(),
                "twoFactorEnabled" => $this->hasTwoFactorEnabled(),
                "pushSubscriptionsCount" => $this->push_subscriptions_count ?? $this->pushSubscriptions()->count(),
            ]),
        ];
    }
}
