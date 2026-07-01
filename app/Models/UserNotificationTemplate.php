<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationTemplate extends Model
{
    protected $fillable = [
        'template_id',
        'user_id',
        'is_active',
        'type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public static function getUserNotificationTemplateSettings($userId)
    {
        return self::where('user_id', $userId)
            ->with('notificationTemplate')
            ->get()
            ->pluck('is_active', 'notificationTemplate.name')
            ->toArray();
    }

    public static function isNotificationActive($templateName, $userId, $type = 'email')
    {
        $template = NotificationTemplate::where('name', $templateName)->first();
        if (!$template) {
            return false;
        }

        return self::where('user_id', $userId)
            ->where('template_id', $template->id)
            ->where('type', $type)
            ->where('is_active', true)
            ->exists();
    }

    public static function setNotificationStatus($templateName, $userId, $type, $isActive)
    {
        $template = NotificationTemplate::where('name', $templateName)->first();
        if (!$template) {
            return false;
        }

        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'template_id' => $template->id,
                'type' => $type
            ],
            ['is_active' => $isActive]
        );
    }
}