<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_links', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('task_id')->unsigned();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->string('link_name', 255);
            $table->string('link_url', 2048);
            $table->text('description')->nullable();
            $table->integer('added_by')->unsigned()->nullable();
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
            $table->integer('last_updated_by')->unsigned()->nullable();
            $table->foreign('last_updated_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        $module = \App\Models\Module::where('module_name', 'tasks')->first();
        if (!is_null($module)) {
            $allPermissionType = \App\Models\PermissionType::where('name', 'all')->first()->id;

            $permissions = [
                'view_task_links',
                'add_task_links',
                'edit_task_links',
                'delete_task_links'
            ];

            foreach ($permissions as $permissionName) {
                $permission = \App\Models\Permission::firstOrCreate(
                    [
                        'name' => $permissionName,
                        'module_id' => $module->id,
                    ],
                    [
                        'display_name' => ucwords(str_replace('_', ' ', $permissionName)),
                        'is_custom' => 1,
                        'allowed_permissions' => \App\Models\Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5
                    ]
                );

                $companies = \App\Models\Company::select('id')->get();
                foreach ($companies as $company) {
                    $role = \App\Models\Role::where('name', 'admin')->where('company_id', $company->id)->first();
                    if ($role) {
                        \App\Models\PermissionRole::firstOrCreate([
                            'permission_id' => $permission->id,
                            'role_id' => $role->id,
                        ], [
                            'permission_type_id' => $allPermissionType
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_links');
        // We explicitly DO NOT delete permission records during rollback. 
        // Since firstOrCreate may have attached to pre-existing rows, deleting them blindly would risk corrupting existing security configurations.
    }
};
