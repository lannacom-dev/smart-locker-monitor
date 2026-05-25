<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrate existing locker status values to the new operational-status vocabulary.
 *
 * old → new mapping:
 *   online      → available
 *   maintenance → in_use
 *   error       → fault
 *   offline     → offline (unchanged)
 *
 * On SQL Server, the enum() schema builder creates a NVARCHAR column with a
 * CHECK constraint. We must drop that constraint before updating the data,
 * then add a new constraint for the new allowed values.
 */
return new class extends Migration
{
    private array $newValues = ['available', 'in_use', 'fault', 'offline', 'disabled'];
    private array $oldValues = ['online', 'offline', 'maintenance', 'error'];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            $this->dropCheckConstraint();
        }

        DB::table('lockers')->where('status', 'online')->update(['status' => 'available']);
        DB::table('lockers')->where('status', 'maintenance')->update(['status' => 'in_use']);
        DB::table('lockers')->where('status', 'error')->update(['status' => 'fault']);

        if (DB::getDriverName() === 'sqlsrv') {
            $vals = implode("', '", $this->newValues);
            DB::statement("ALTER TABLE lockers ADD CONSTRAINT CK_lockers_status CHECK (status IN ('{$vals}'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            $this->dropCheckConstraint();
        }

        DB::table('lockers')->where('status', 'available')->update(['status' => 'online']);
        DB::table('lockers')->where('status', 'in_use')->update(['status' => 'maintenance']);
        DB::table('lockers')->where('status', 'fault')->update(['status' => 'error']);
        DB::table('lockers')->where('status', 'disabled')->update(['status' => 'offline']);

        if (DB::getDriverName() === 'sqlsrv') {
            $vals = implode("', '", $this->oldValues);
            DB::statement("ALTER TABLE lockers ADD CONSTRAINT CK_lockers_status CHECK (status IN ('{$vals}'))");
        }
    }

    /**
     * Drop whichever CHECK constraint SQL Server auto-generated for lockers.status.
     * The constraint name is auto-generated (e.g. CK__lockers__status__7AF13DF7),
     * so we look it up dynamically from sys.check_constraints.
     */
    private function dropCheckConstraint(): void
    {
        DB::statement("
            DECLARE @cn NVARCHAR(200);
            SELECT @cn = name
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('lockers')
              AND parent_column_id  = COLUMNPROPERTY(OBJECT_ID('lockers'), 'status', 'ColumnId');
            IF @cn IS NOT NULL
                EXEC('ALTER TABLE lockers DROP CONSTRAINT [' + @cn + ']');
        ");
    }
};
