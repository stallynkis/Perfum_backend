<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Obtener todas las notificaciones
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::query()
            ->with(['order', 'user', 'vendor', 'contactForm'])
            ->orderBy('created_at', 'desc');

        // Filtros opcionales
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('read')) {
            $query->where('read', $request->boolean('read'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Obtener una notificación específica
     */
    public function show($id): JsonResponse
    {
        $notification = Notification::with(['order', 'user', 'vendor', 'contactForm'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Crear una notificación
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:order,sale,contact,system,payment,stock',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:high,medium,low',
            'related_tab' => 'nullable|string',
            'related_id' => 'nullable|string',
            'order_id' => 'nullable|exists:orders,id',
            'user_id' => 'nullable|exists:users,id',
            'vendor_id' => 'nullable|exists:users,id',
            'contact_form_id' => 'nullable|exists:contact_forms,id',
            'data' => 'nullable|array'
        ]);

        $notification = Notification::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notificación creada exitosamente',
            'data' => $notification
        ], 201);
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
            'data' => $notification
        ]);
    }

    /**
     * Marcar notificación como no leída
     */
    public function markAsUnread($id): JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como no leída',
            'data' => $notification
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = Notification::unread()->update(['read' => true]);

        return response()->json([
            'success' => true,
            'message' => "{$count} notificaciones marcadas como leídas",
            'count' => $count
        ]);
    }

    /**
     * Eliminar una notificación
     */
    public function destroy($id): JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada exitosamente'
        ]);
    }

    /**
     * Eliminar todas las notificaciones leídas
     */
    public function clearRead(): JsonResponse
    {
        $count = Notification::where('read', true)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} notificaciones eliminadas",
            'count' => $count
        ]);
    }

    /**
     * Eliminar todas las notificaciones
     */
    public function clearAll(): JsonResponse
    {
        $count = Notification::count();
        Notification::truncate();

        return response()->json([
            'success' => true,
            'message' => "{$count} notificaciones eliminadas",
            'count' => $count
        ]);
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::unread()->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Notification::count(),
            'unread' => Notification::unread()->count(),
            'by_type' => [
                'order' => Notification::ofType('order')->count(),
                'sale' => Notification::ofType('sale')->count(),
                'contact' => Notification::ofType('contact')->count(),
                'system' => Notification::ofType('system')->count(),
                'payment' => Notification::ofType('payment')->count(),
                'stock' => Notification::ofType('stock')->count(),
            ],
            'by_priority' => [
                'high' => Notification::where('priority', 'high')->count(),
                'medium' => Notification::where('priority', 'medium')->count(),
                'low' => Notification::where('priority', 'low')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
