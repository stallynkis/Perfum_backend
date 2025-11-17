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
            // Para MySQL/PostgreSQL, usar try-catch para evitar errores de índices duplicados
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Índice ya existe, continuar
            }
            
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index(['user_id', 'read']);
                });
            } catch (\Exception $e) {
                // Índice ya existe, continuar
            }
            
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at']);
                });
            } catch (\Exception $e) {
                // Índice ya existe, continuar
            }

            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('role');
                });
            } catch (\Exception $e) {
                // Índice ya existe, continuar
            }

            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->index('featured');
                });
            } catch (\Exception $e) {
                // Índice ya existe, continuar
            }
            
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->index(['status', 'featured']);
                });
            } catch (\Exception $e) {
                // Índice ya existe, continuar
            }
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
