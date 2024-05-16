<?php

namespace Namnb\Authorization\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Role\Entities\Role;

class UpdateRolesCommand extends Command
{
    const NAME_PROVIDER = 'App/Providers/SimpleRoleServiceProvider.php';
    const NAME_STUB = 'stubs/simple_role_provider.stub';
    const ANCHOR_DEFINE = '{{ gate_define }}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:role {--r|reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update new role from role config';

    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $isReset = $this->option('reset');
        $strGateDefine = "";
        $rolesConfig = config('role.roles');

        DB::beginTransaction();
        if ($isReset) {
            try {
                if (!$this->confirm('Do you confirm to reset roles?')) return;
                DB::table('user_extend_role')->delete();
                DB::table('extend_roles')->delete();
                // DB::table('group_permissions')->delete();

                $this->updateNewRole($rolesConfig, $strGateDefine);
                $this->createSimpleRoleProvider($strGateDefine);

                $this->info('Reset roles success');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
                Log::info($e->getMessage());
            }
        } else {
            try {
                if (!$this->confirm('Do you confirm to update roles?')) return;

                $this->updateRoleNoExist($rolesConfig, $strGateDefine);
                $this->createSimpleRoleProvider($strGateDefine);

                $this->info('Update roles success');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
            }
        }
    }

    public function updateNewRole($roles, &$strGateDefine, $parentLevel = null, $parentRoleId = null)
    {
        foreach ($roles as $index => $roleConfig) {
            $level = $parentLevel == null ? $index : $parentLevel . '_' . $index;
            $roleId = $this->insertRole($roleConfig['name'], $roleConfig['name_show'], $level, $parentRoleId);
            $strGateDefine .= $this->addGateDefineString($roleConfig['name']);

            if (isset($roleConfig['child_roles'])) {
                $this->updateNewRole($roleConfig['child_roles'], $strGateDefine, $level, $roleId);
            }
        }
    }

    public function updateRoleNoExist($roles, &$strGateDefine, $parentLevel = null, $parentRoleId = null)
    {
        foreach ($roles as $index => $roleConfig) {
            if (DB::table('extend_roles')->where('name', $roleConfig['name'])->count() > 0) {
                $role = DB::table('extend_roles')->where('name', $roleConfig['name'])->first();
                $strGateDefine .= $this->addGateDefineString($role->name);

                if (isset($roleConfig['child_roles'])) {
                    $this->updateRoleNoExist($roleConfig['child_roles'], $strGateDefine, $role->level, $role->id);
                }
            } else {
                $level = $parentLevel == null ? $index : $parentLevel . '_' . $index;
                if ($role = Role::where('level', $level)->first()) {
                    $roleId = $this->updateRole($role, $roleConfig['name'], $roleConfig['name_show'], $level, $parentRoleId);
                } else {
                    $roleId = $this->insertRole($roleConfig['name'], $roleConfig['name_show'], $level, $parentRoleId);
                }

                $strGateDefine .= $this->addGateDefineString($roleConfig['name']);

                if (isset($roleConfig['child_roles'])) {
                    $this->updateRoleNoExist($roleConfig['child_roles'], $strGateDefine, $level, $roleId);
                }
            }
        }
    }

    public function createSimpleRoleProvider($strGateDefine)
    {
        $stubPath = module_path('Role') . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . self::NAME_STUB;
        $stub = $this->files->get($stubPath);
        $stub = str_replace(self::ANCHOR_DEFINE, $strGateDefine, $stub);
        $path = self::NAME_PROVIDER;

        $this->files->put($path, $stub);
    }

    public function addGateDefineString($roleName)
    {
        dd('Thay đổi chỗ này: ExtendRole');
        return "Gate::define('" . $roleName . "', [\Modules\Role\Policies\RolePolicy::class, 'checkRole']);\r\n\t\t";
    }

    public function insertRole($name, $nameShow, $level, $parentId)
    {
        return DB::table('extend_roles')->insertGetId([
            'name' => $name,
            'name_show' => $nameShow,
            'action' => $name,
            'level' => $level,
            'parent_id' => $parentId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function updateRole($role, $name, $nameShow, $level, $parentId)
    {
        $role->update([
            'name' => $name,
            'name_show' => $nameShow,
            'action' => $name,
            'level' => $level,
            'parent_id' => $parentId,
            'updated_at' => Carbon::now(),
        ]);

        return $role->id;
    }
}
