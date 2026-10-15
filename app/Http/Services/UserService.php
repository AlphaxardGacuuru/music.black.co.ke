<?php

namespace App\Http\Services;

use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class UserService extends Service
{
	public function index(Request $request): LengthAwarePaginator|Collection
	{
		if ($request->filled('idAndName')) {
			return User::query()
				->select('id', 'name')
				->orderBy('id', 'DESC')
				->get();
		}

		$query = $this->search(User::query(), $request);

		if ($request->boolean('excludeSelf') && auth()->check()) {
			$query = $query->whereKeyNot(auth()->id());
		}

		return $query
			->withCount('pushSubscriptions')
			->orderBy('id', 'DESC')
			->paginate($request->integer('per_page', 15));
	}

	/**
	 * Profiles are addressed by "@username" (the music app's profile URLs) or
	 * by id (the admin screens).
	 */
	public function show(int|string $id): User
	{
		if (is_string($id) && str_starts_with($id, '@')) {
			return User::query()->where('username', $id)->firstOrFail();
		}

		return User::query()->findOrFail($id);
	}

	public function store(Request $request): array
	{
		$user = User::query()->create([
			'name' => $request->string('name')->toString(),
			'email' => $request->string('email')->toString(),
			'password' => Hash::make($request->string('password')->toString()),
			'phone' => $request->input('phone'),
			'settings' => $request->input('settings'),
		]);

		if ($request->filled('userRoles')) {
			$user->syncRoles($request->input('userRoles'));
		}

		return [true, 'Account Created', $user->fresh()];
	}

	public function update(Request $request, int|string $id): array
	{
		$user = User::query()->findOrFail($id);

		if ($request->filled('name')) {
			$user->name = $request->string('name')->toString();
		}

		if ($request->filled('email')) {
			$user->email = $request->string('email')->toString();
		}

		if ($request->filled('phone')) {
			$user->phone = $request->input('phone');
		}

		if ($request->filled('password')) {
			$user->password = Hash::make($request->string('password')->toString());
		}

		if ($request->exists('settings')) {
			$user->settings = $request->input('settings');
		}

		if ($request->has('verified')) {
			$user->verified = $request->boolean('verified');
		}

		if ($request->filled('bio')) {
			$user->bio = $request->input('bio');
		}

		if ($request->filled('withdrawal')) {
			$user->withdrawal = $request->input('withdrawal');
		}

		// Becoming a musician is one-way, and starts the artist off with a
		// "Singles" album for both videos and audios.
		$becameMusician = $request->filled('accountType') && $user->account_type === 'normal';

		if ($becameMusician) {
			$user->account_type = $request->input('accountType');
		}

		$saved = DB::transaction(function () use ($user, $becameMusician) {
			$saved = $user->save();

			if ($becameMusician) {
				$user->videoAlbums()->create([
					'name' => 'Singles',
					'cover' => 'video-album-covers/musical-note.png',
					'released' => now(),
				]);

				$user->audioAlbums()->create([
					'name' => 'Singles',
					'cover' => 'audio-album-covers/musical-note.png',
					'released' => now(),
				]);
			}

			return $saved;
		});

		if ($request->filled('userRoles')) {
			$user->syncRoles($request->input('userRoles'));
		}

		return [$saved, 'Account Updated', $user->fresh()];
	}

	public function destroy(int|string $id): array
	{
		$user = User::query()->findOrFail($id);
		$deleted = $user->delete();

		return [$deleted, $user->name . ' deleted'];
	}

	/**
	 * Update the user's own profile, resetting email verification when the
	 * email address changes.
	 *
	 * @param  array<string, mixed>  $attributes
	 */
	public function updateProfile(User $user, array $attributes): void
	{
		$user->fill($attributes);

		if ($user->isDirty('email')) {
			$user->email_verified_at = null;
		}

		$user->save();
	}

	public function updatePassword(User $user, string $password): void
	{
		$user->update(['password' => $password]);
	}

	/**
	 * Set a new password from a reset link, rotating the remember token so
	 * any "remember me" sessions on other devices are signed out.
	 */
	public function resetPassword(User $user, string $password): void
	{
		$user->forceFill([
			'password' => Hash::make($password),
			'remember_token' => Str::random(60),
		])->save();
	}

	/**
	 * Replace a user's avatar with an already-stored file, deleting the old
	 * one unless it's the default.
	 */
	public function updateAvatar(int|string $id, string $avatarPath): void
	{
		$user = User::findOrFail($id);

		if ($user->avatar != '/storage/avatars/male_avatar.png') {
			Storage::disk('public')->delete(substr($user->avatar, 9));
		}

		$user->avatar = $avatarPath;
		$user->save();
	}

	/**
	 * Musicians to discover, 10 per page, leaving out the viewer and the
	 * house @blackmusic account.
	 */
	public function artists(): LengthAwarePaginator
	{
		return User::query()
			->where('account_type', 'musician')
			->when($this->id, fn($query) => $query->whereKeyNot($this->id))
			->where('username', '!=', '@blackmusic')
			->paginate(10);
	}

	public function auth(): UserResource|Response
	{
		if (! auth('sanctum')->check()) {
			return response(['message' => 'Not Authenticated'], 401);
		}

		return new UserResource(auth('sanctum')->user());
	}

	protected function search($query, Request $request)
	{
		if ($request->filled('roleId')) {
			$roleName = Role::query()->findOrFail($request->input('roleId'))->name;
			$query = $query->role($roleName);
		}

		if ($request->filled('name')) {
			$query = $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
		}

		return $query;
	}
}
