<?php


namespace App\Http\Controllers\Page;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\StudentTopic;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Requests\UserUpdateInforProfileRequest;
use App\Http\Requests\OutlineRequest;
use App\Http\Requests\ThesisBookRequest;
use App\Helpers\MailHelper;
use App\Http\Requests\ChangePasswordRequest;

class UserController extends Controller
{
    public function __construct()
    {
        view()->share([
            'gender' => User::GENDER,
            'status_outline' => StudentTopic::STATUS_OUTLINE,
            'status' => StudentTopic::STATUS
        ]);
    }
    //
    public function profile()
    {
        $user = Auth::guard('students')->user();
        return view('page.user.index', compact('user'));
    }

    public function updateProfile(UserUpdateInforProfileRequest $request)
    {
        $data = $request->except('images', '_token');

        \DB::beginTransaction();
        try {
            $id = Auth::guard('students')->user()->id;

            $user = User::find($id);

            if (isset($request->images) && !empty($request->images)) {
                $image = upload_image('images');
                if ($image['code'] == 1)
                    $user->avatar = $image['name'];
            }

            $user->update($data);
            \DB::commit();
            return redirect()->route('user.profile')->with('success','Chỉnh sửa thành công');
        } catch (\Exception $exception) {
            \DB::rollBack();
            return redirect()->route('user.profile')->with('error', 'Đã xảy ra lỗi khi lưu dữ liệu');
        }
    }

    public function registerDetails()
    {
        $user = Auth::guard('students')->user();

        $param = [
            'st_student_id' => $user->id,
            'st_course_id' => $user->course_id
        ];
        $studentTopic = StudentTopic::with(['topic' => function($topic) {
            $topic->with(['topic', 'department']);
        }, 'teacher'])->where($param)->first();

        if (!$studentTopic)
        {
            return redirect()->back()->with('error', 'Chưa đăng ký đề tài');
        }
        $numberStudent = 0;
        if ($studentTopic) {
            $numberStudent = StudentTopic::where(['st_course_id' =>  $user->course_id, 'st_topic_id' => $studentTopic->st_topic_id])->count();
        }

        return view('page.topic.register_details', compact('studentTopic', 'numberStudent'));
    }

    public function outline()
    {
        $user = Auth::guard('students')->user();

        $param = [
            'st_student_id' => $user->id,
            'st_course_id' => $user->course_id
        ];
        $studentTopic = StudentTopic::with(['topic' => function($topic) {
            $topic->with(['topic', 'department']);
        }, 'teacher'])->where($param)->first();

        return view('page.topic.outline', compact('studentTopic'));


    }

    public function postOutline(OutlineRequest $request, $id)
    {
        $user = Auth::guard('students')->user();

        $studentTopic = StudentTopic::with(['teacher', 'topic' => function ($topic) {
            $topic->with('topic');
        }])->find($id);

        if (!$studentTopic) {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể nộp đề cương');
        }
        if (!checkInTime($studentTopic->topic->tc_start_outline, $studentTopic->topic->tc_end_outline)) {
            return redirect()->back()->with('error', 'Hết thời gian nộp đề cương');
        }
        try {
            if($request->hasfile('outline_part'))
            {
                $file = $request->file('outline_part');
                $st_outline_part = date('YmdHms').'-'. $user->code .'-'.$file->getClientOriginalName();
                $file->move(public_path().'/uploads/documents/', date('YmdHms'). $user->code .$file->getClientOriginalName());
            }

            $studentTopic->st_outline = $request->st_outline;
            $studentTopic->st_outline_part = $st_outline_part;
            $studentTopic->st_status_outline = 1;

            if ($studentTopic->save()) {
                // send mail teacher
                $dataMail = [
                    'subject' => 'Thông báo sinh viên ' .$user->name . ' đã nộp đề cương',
                    'name_teacher' => isset($studentTopic->teacher) ? $studentTopic->teacher->name : '',
                    'name_student' => $user->name,
                    'email' => isset($studentTopic->teacher) ? $studentTopic->teacher->email : '',
                    'topic' => $studentTopic->topic->topic->t_title,
                    'title' => $studentTopic->st_outline,
                    'link_dowload' => $studentTopic->st_outline_part,
                    'outline_status' => 1,
                    'teacher_status' => 0
                ];

                MailHelper::sendMailOutline($dataMail);
            }
            return redirect()->back()->with('success','Nộp thành công đề cương ');
        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể nộp đề cương');
        }

    }


    public function thesisBook()
    {
        $user = Auth::guard('students')->user();

        $param = [
            'st_student_id' => $user->id,
            'st_course_id' => $user->course_id
        ];
        $studentTopic = StudentTopic::with(['topic' => function($topic) {
            $topic->with(['topic', 'department']);
        }, 'teacher'])->where($param)->first();
        return view('page.topic.thesis_book', compact('studentTopic'));
    }

    public function postThesisBook(ThesisBookRequest $request, $id)
    {
        $user = Auth::guard('students')->user();

        $studentTopic = StudentTopic::with(['teacher', 'topic' => function ($topic) {
            $topic->with('topic');
        }])->find($id);

        if (!$studentTopic) {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể nộp quyển khóa luận');
        }
        if (!checkInTime($studentTopic->topic->tc_start_thesis_book, $studentTopic->topic->tc_end_thesis_book)) {
            return redirect()->back()->with('error', 'Hết thời gian nộp quyển khóa luận');
        }
        try {
            if($request->hasfile('thesis_book'))
            {
                $file = $request->file('thesis_book');
                $thesis_book = date('YmdHms').'-'. $user->code .'-'.$file->getClientOriginalName();
                $file->move(public_path().'/uploads/documents/', date('YmdHms'). $user->code .$file->getClientOriginalName());
            }

            $studentTopic->st_thesis_book = $request->st_thesis_book;
            $studentTopic->st_thesis_book_part = $thesis_book;
            $studentTopic->st_status_thesis_book = 1;

            if ($studentTopic->save()) {
                // send mail teacher
                $dataMail = [
                    'subject' => 'Thông báo sinh viên ' .$user->name . ' đã nộp khóa luận',
                    'name_teacher' => isset($studentTopic->teacher) ? $studentTopic->teacher->name : '',
                    'name_student' => $user->name,
                    'email' => isset($studentTopic->teacher) ? $studentTopic->teacher->email : '',
                    'topic' => $studentTopic->topic->topic->t_title,
                    'title' => $studentTopic->st_thesis_book,
                    'link_dowload' => $studentTopic->st_thesis_book_part,
                    'outline_status' => 0,
                    'teacher_status' => 0
                ];

                MailHelper::sendMailOutline($dataMail);

            }
            return redirect()->back()->with('success','Nộp thành công khóa luận ');
        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể nộp khóa luậ');
        }
    }

    public function cancel($id)
    {
        $studentTopic = StudentTopic::with(['teacher', 'topic' => function ($topic) {
            $topic->with('topic');
        }])->find($id);

        if (!$studentTopic) {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể hủy đề tài');
        }

        if (!checkInTime($studentTopic->topic->tc_start_time, $studentTopic->topic->tc_end_time)) {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể hủy đề tài');
        }
        try {
            if ($studentTopic->delete()) {
                $user = Auth::guard('students')->user();

                $dataMail = [
                    'subject' => 'Thông báo sinh viên ' .$user->name . ' đã hủy đề tài',
                    'name_teacher' => isset($studentTopic->teacher) ? $studentTopic->teacher->name : '',
                    'name_student' => $user->name,
                    'email' => isset($studentTopic->teacher) ? $studentTopic->teacher->email : '',
                    'topic' => $studentTopic->topic->topic->t_title,
                ];

                MailHelper::sendMailCancel($dataMail);
            }
            return redirect()->route('user.home')->with('success','Hủy thành công đề tài');
        } catch (\Exception $exception)
        {
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể hủy đề tài');
        }
    }

    public function changePassword()
    {
        return view('page.user.change_password');
    }

    public function postChangePassword(ChangePasswordRequest $request)
    {
        \DB::beginTransaction();
        try {
            $user =  User::find(Auth::guard('students')->user()->id);
            $user->password = bcrypt($request->password);
            $user->save();
            \DB::commit();
            Auth::guard('students')->logout();
            return redirect()->route('user.login')->with('success', 'Đổi mật khẩu thành công.');
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Đã xảy ra lỗi không thể đổi mật khẩu');
        }
    }
}
