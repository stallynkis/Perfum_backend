<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'sqlite') {
            // Para SQLite, verificar si el índice existe antes de crearlo
            $existingIndexes = $connection->select("SELECT name FROM sqlite_master WHERE type='index'");
            $indexNames = array_column($existingIndexes, 'name');
            
            // Índices para notifications
            Schema::table('notifications', function (Blueprint $table) use ($indexNames) {
                if (!in_array('notifications_created_at_index', $indexNames)) {
                    $table->index('created_at');
                }
                if (!in_array('notifications_user_id_read_index', $indexNames)) {
                    $table->index(['user_id', 'read']);
                }
                if (!in_array('notifications_user_id_created_at_index', $indexNames)) {
                    $table->index(['user_id', 'created_at']);
                }
            });

            // Índices para users
            Schema::table('users', function (Blueprint $table) use ($indexNames) {
                if (!in_array('users_role_index', $indexNames)) {
                    $table->index('role');
                }
            });

            // Índices para products
            Schema::table('products', function (Blueprint $table) use ($indexNames) {
                if (!in_array('products_featured_index', $indexNames)) {
                    $table->index('featured');
                }
                if (!in_array('products_status_featured_index', $indexNames)) {
                    $table->index(['status', 'featured']);
                }
            });
        } else {
            // Para MySQL/PostgreSQL, verificar si el índice existe antes de crearlo
            $sm = $connection->getDoctrineSchemaManager();
            
            // Índices para notifications
            Schema::table('notifications', function (Blueprint $table) use ($sm) {
                $indexes = $sm->listTableIndexes('notifications');
                $indexNames = array_keys($indexes);
                
                if (!in_array('notifications_created_at_index', $indexNames)) {
                    $table->index('created_at');
                }
                if (!in_array('notifications_user_id_read_index', $indexNames)) {
                    $table->index(['user_id', 'read']);
                }
                if (!in_array('notifications_user_id_created_at_index', $indexNames)) {
                    $table->index(['user_id', 'created_at']);
                }
            });

            // Índices para users
            Schema::table('users', function (Blueprint $table) use ($sm) {
                $indexes = $sm->listTableIndexes('users');
                $indexNames = array_keys($indexes);
                
                if (!in_array('users_role_index', $indexNames)) {
                    $table->index('role');
                }
            });

            // Índices para products
            Schema::table('products', function (Blueprint $table) use ($sm) {
                $indexes = $sm->listTableIndexes('products');
                $indexNames = array_keys($indexes);
                
                if (!in_array('products_featured_index', $indexNames)) {
                    $table->index('featured');
                }
                if (!in_array('products_status_featured_index', $indexNames)) {
                    $table->index(['status', 'featured']);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'read']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['role']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['featured']);
            $table->dropIndex(['status', 'featured']);
        });
    }
};
