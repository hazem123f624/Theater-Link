<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Users\Interfaces\UsersInterface;
use Modules\Users\Models\User;
use Modules\Users\Resources\UserResource;

class ManageUsersController extends Controller
{
    use HttpResponsesTrait;

    private UsersInterface $repository;

    public function __construct(UsersInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of users for the Super Admin.
     */
    public function index()
    {
        $request = $this->handelRequests(requests: request()->all());
        $data = $this->repository->data(request: $request);
        $data['data'] = UserResource::collection($data['data']);

        return $this->success('users::messages.users_retrieved_successfully', data: $data);
    }

    public function show(User $user)
    {
        return $this->success('users::messages.user_details_retrieved_successfully', data: new UserResource($user));
    }

    public function switchActive(User $user)
    {
        $data = $this->repository->switchActive($user);

        return $this->success('users::messages.user_status_updated_successfully', data: new UserResource($data));
    }

    /**
     * Remove the specified user (Soft Delete).
     */
    public function destroy(User $user)
    {
        $this->repository->delete($user);

        return $this->success('users::messages.user_deleted_successfully', data: []);
    }

    /**
     * Recycle Bin for Users.
     */
    public function trash()
    {
        $allRequested = request()->all();
        $allRequested['only_trashed'] = true;
        $request = $this->handelRequests(requests: $allRequested);
        $data = $this->repository->data(request: $request);
        $data['data'] = UserResource::collection($data['data']);

        return $this->success('users::messages.trashed_users_retrieved_successfully', data: $data);
    }

    public function restore(User $user)
    {
        $data = $this->repository->restore($user);

        return $this->success('users::messages.user_restored_successfully', data: new UserResource($data));
    }

    public function remove(User $user)
    {
        $this->repository->forceDelete($user);

        return $this->success('users::messages.user_force_deleted_permanently', data: []);
    }
}
