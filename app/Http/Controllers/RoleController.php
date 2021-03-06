<?php

namespace ActivismeBE\Http\Controllers;

use ActivismeBE\Repositories\PermissionRepository;
use ActivismeBE\Repositories\RoleRepository;
use ActivismeBE\Traits\Authorizable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Class RoleController
 *
 * If tour building a project don't edit these file. Because this file will be overwritten.
 * When we are updated our project skeleton. And if you found an issue in this controller
 * User the following links.
 *
 * @url https://github.com/CPSB/Skeleton-project
 * @url https://github.com/CPSB/Skeleton-project/issues
 */
class RoleController extends Controller
{
    use Authorizable;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var PermissionRepository
     */
    private $permissionRepository;

    /**
     * Create a new controller instance.
     *
     * @param RoleRepository       $roleRepository
     * @param PermissionRepository $permissionRepository
     *
     * @return void
     */
    public function __construct(RoleRepository $roleRepository, PermissionRepository $permissionRepository)
    {
        $this->middleware('banned');
        $this->middleware('lang');

        $this->roleRepository       = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles       = $this->roleRepository->all();
        $permissions = $this->permissionRepository->all();

        return view('role.index', compact('roles', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, ['name' => 'required|unique:roles']);

        if (Role::create($request->only('name'))) {
            flash(trans('roles.flash-success-role-add'));
        }

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer                   $id        The primary key in the storage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if ($role = $this->roleRepository->findRole($id)) {
            if ($this->roleRepository->isAdmin($role->name)) {  // admin role has everything
                $role->syncPermissions($this->permissionRepository->all());
                return redirect()->route('roles.index');
            }

            $permissions = $request->get('permissions', []);
            $role->syncPermissions($permissions);
            flash(trans('roles.flash-success-role-updated', ['name' => $role->name]));
        } else {
            flash()->error(trans('roles.flash-error-role-updated', ['id' => $id]));
        }

        return redirect()->route('roles.index');
    }

    /**
     * Delete a permission group in the database.
     *
     * @param  integer $roleId the permission group in the database.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($roleId)
    {
        try {
            $role = $this->roleRepository->findRole($roleId);
            $role->syncPermissions([]); // Empty relation for clearing the permissions relation.
            $role->delete();

            flash(trans('roles.flash-success-role-delete'));
            return redirect()->route('roles.index');
        } catch (ModelNotFoundException $modelNotFoundException) {
            flash(trans('roles.flash-error-role-delete'))->error();
            return redirect()->route('roles.index');
        }
    }
}
