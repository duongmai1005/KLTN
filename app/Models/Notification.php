<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    const TYPES = [
        1 => 'Thông báo thời gian đăng ký đề tài',
        2 => 'Thông báo thời gian nộp đề cương',
        3 => 'Thông báo thời gian nộp đề cương',
        4 => 'Thông báo thời gian nộp đề tài',
        5 => 'Thông báo lịch bảo vệ / nộp khóa luận',
    ];
    const SEND_TO = [
        1 => 'Gửi cho giáo viên',
        2 => 'Gửi cho sinh viên',
    ];
    const STATUS = [
        1 => 'Đã duyệt',
        2 => 'Chưa duyệt',
    ];
    protected $table = 'notifications';

    protected $fillable = [
        'n_course_id', 'n_title', 'n_type', 'n_content', 'n_from_date', 'n_end_date', 'n_send_to', 'n_status', 'created_at','updated_at'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'n_course_id', 'id');
    }

    public function notificationUsers()
    {
        return $this->hasMany(NotificationUser::class, 'nu_notification_id', 'id');
    }
}
