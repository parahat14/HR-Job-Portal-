<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\ViewCareer;
use App\Models\Autonbr;
use App\Models\T_Message;
use App\Models\Attachment;
use App\Models\M_approval;
use App\Models\M_approval_other;
use App\Models\T_approval;
use App\Models\Company;
use App\Models\Dept;
use App\Models\JobLevel;
use App\Models\JobResponsiblities;
use App\Models\JobQualification;
use App\Models\Usercpny;
use App\Models\Userdept;
use App\Models\User;
use App\Models\Jobposting;
use App\Models\JobpostingResponsiblities;
use App\Models\JobpostingQualification;
use App\Models\AutonbrJobportal;
use App\Models\Career;
use App\Models\Applicant;
use App\Models\ApplicantCourse;
use App\Models\ApplicantEducation;
use App\Models\ApplicantFamily;
use App\Models\ApplicantLanguage;
use App\Models\ApplicantMarital;
use App\Models\ApplicantSW;
use App\Models\ApplicantSkill;
use App\Models\ApplicantWorking;
use App\Models\JobApplyStep;
use App\Models\Mschecklist;
use App\Models\Trchecklist;
use App\Models\MsAssessment;
use App\Models\TrAssessment;
use App\Models\TrAssessmentdetail;
use App\Models\Agenda;
use Mail;
use PhpOffice\PhpWord\TemplateProcessor;
use PDF;
// use Storage;
use Illuminate\Support\Str;
use App\Models\JobApply;
use App\Models\MPsychotest;
use App\Models\Payrollconfirm;
use App\Models\Msonboarding;
use App\Models\Tronboarding;
use Illuminate\Support\Facades\Crypt;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Storage;

class CareerController extends Controller
{
    public function index()
    {
        $incompletedprofile = ViewCareer::where('status_app', 'H')->count();
        $completedprofile = ViewCareer::where('status_app', 'P')->count();
        $nocandidate = ViewCareer::where('status', 'H')->count();
        $candidate = ViewCareer::where('status', 'P')->count();
        $join = ViewCareer::where('status', 'C')->count();
              
        return view('pages.careers.careers', compact('incompletedprofile', 'completedprofile', 'nocandidate', 'candidate','join'));
    }

    public function stats(Request $request)
    {
        $cpnyid = $request->query('cpnyid');
        
        $query = ViewCareer::query();
        if (!empty($cpnyid)) {
            $query->where('cpnyid', $cpnyid);
        }

        return response()->json([
            'incompletedprofile' => (clone $query)->where('status_app', 'H')->count(),
            'completedprofile' => (clone $query)->where('status_app', 'P')->count(),
            'nocandidate' => (clone $query)->where('status', 'H')->count(),
            'candidate' => (clone $query)->where('status', 'P')->count(),
            'join' => (clone $query)->where('status', 'C')->count(),
        ]);
    }

    
    public function json(Request $request)
    {
        $status_app = $request->query('status_app');
        $status = $request->query('status');
        $cpnyid = $request->query('cpnyid');

        $query = ViewCareer::query();

        if (!empty($status_app)) {
            $query->where('status_app', $status_app);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($cpnyid)) {
            $query->where('cpnyid', $cpnyid);
        }

        $career = $query->orderBy('id', 'desc')->get();
        return response()->json(['data' => $career]);
    }

  
    public function jsonxxx(Request $request)
    {
        $status_app = $request->query('status_app');
        $status = $request->query('status');
        $cpnyid = $request->query('cpnyid');

        $query = ViewCareer::query();

        if (!empty($status_app)) {
            $query->where('status_app', $status_app);
        } elseif (!empty($status)) {
            $query->where('status', $status);
        } elseif (!empty($cpnyid)) {
            $query->where('cpnyid', $cpnyid);
        }
        

        $career = $query->orderBy('id', 'desc')->get();

        return response()->json(['data' => $career]);
    }


    public function showCareer($id)
    {        
        $user = Auth::user();       

        if (!$user) {
            return redirect()->route('login');
        }

        $datenow = Carbon::now()->format('Y-m-d');       
        $timenow = date('Y-m-d H:i:s');
        $career = ViewCareer::findOrFail($id);   
        $job_apply = Career::where('docid', $career->docid)->first();

        if ($user->departmentid=='HRGA') {           
            $job_apply->is_read = 'Y';
            $job_apply->save();
        }
        // dd($job_apply);
        $applicant = Applicant::where('applicant_id', $career->applicant_id)->first();
        $applicant_family = ApplicantFamily::where('applicant_id', $career->applicant_id)->get();
        $applicant_marital = ApplicantMarital::where('applicant_id', $career->applicant_id)->get();
        $applicant_education = ApplicantEducation::where('applicant_id', $career->applicant_id)->get();
        $applicant_working = ApplicantWorking::where('applicant_id', $career->applicant_id)->get();
        $applicant_language = ApplicantLanguage::where('applicant_id', $career->applicant_id)->get();
        $applicant_course = ApplicantCourse::where('applicant_id', $career->applicant_id)->get();
        $applicant_sw = ApplicantSW::where('applicant_id', $career->applicant_id)->get();
        $applicant_skill = ApplicantSkill::where('applicant_id', $career->applicant_id)->get();

        $jobapplystep = JobApplyStep::leftjoin('hr_ms_job_step', 'hr_trx_job_apply_step.step_id', '=', 'hr_ms_job_step.step_id')                                      
            ->select('hr_trx_job_apply_step.*', 'hr_ms_job_step.step_descr')   
            ->where('hr_trx_job_apply_step.docid',$career->docid)       
            ->where('hr_trx_job_apply_step.status','<>','X')
            ->orderBy('hr_trx_job_apply_step.step_order', 'ASC')
            ->get();

        $jobposting = Jobposting::where('docid', $career->docidposting)->first();        
        $jobres = JobpostingResponsiblities::where('docid', $career->docidposting)->get();
        $jobqua = JobpostingQualification::where('docid', $career->docidposting)->get();

        $tr_checklist = Trchecklist::leftjoin('hr_ms_doc_checklist', 'hr_trx_doc_checklist.checklist_id', '=', 'hr_ms_doc_checklist.checklist_id')                                      
            ->select('hr_trx_doc_checklist.*', 'hr_ms_doc_checklist.checklist_descr')  
            ->where('hr_trx_doc_checklist.jobapply_id',$career->docid)        
            ->orderBy('hr_trx_doc_checklist.step_order', 'ASC')
            ->get(); 
       
        // ========== HC ASSESSMENT ==========
        $assessmentGroups = [];
        $tr_assessment = TrAssessment::where('jobapply_id', $career->docid)
            ->where('type','hc')
            ->first();     

        if ($tr_assessment) {
            $assessmentData = TrAssessmentdetail::leftjoin('hr_ms_interview_assessment', 'hr_trx_interview_assessment_detail.assessment_id', '=', 'hr_ms_interview_assessment.assessment_id')                                      
                ->select('hr_trx_interview_assessment_detail.*', 'hr_ms_interview_assessment.assessment_group', 'hr_ms_interview_assessment.assessment_descr')          
                ->where('hr_trx_interview_assessment_detail.docid', $tr_assessment->docid)
                ->orderBy('hr_ms_interview_assessment.step_order_group', 'ASC')
                ->orderBy('hr_ms_interview_assessment.step_order', 'ASC')            
                ->get()
                ->groupBy('step_order_group');

            foreach ($assessmentData as $group) {
                $first = $group->first();
                $assessmentGroups[] = [
                    'assessment_id' => $first->assessment_id,
                    'assessment_group' => $first->assessment_group,
                    'assessment_type' => $first->assessment_type,
                    'selected_score' => $group
                        ->filter(fn($item) => $item->assessment_score_value > 0)
                        ->pluck('assessment_score_value')
                        ->first() ?? 0,
                    'options' => $group->map(function ($item) {
                        return [
                            'assessment_score' => $item->assessment_score,
                            'assessment_descr' => $item->assessment_descr
                        ];
                    })->values()->toArray()
                ];
            }
        }

        // ========== USER ASSESSMENT ==========
        $assessmentGroupsUser = [];
        $tr_assessment_user = TrAssessment::where('jobapply_id', $career->docid)
            ->where('type','user')
            ->first();     

        if ($tr_assessment_user) {
            $assessmentData_user = TrAssessmentdetail::leftjoin('hr_ms_interview_assessment', 'hr_trx_interview_assessment_detail.assessment_id', '=', 'hr_ms_interview_assessment.assessment_id')                                      
                ->select('hr_trx_interview_assessment_detail.*', 'hr_ms_interview_assessment.assessment_group', 'hr_ms_interview_assessment.assessment_descr')          
                ->where('hr_trx_interview_assessment_detail.docid', $tr_assessment_user->docid)
                ->orderBy('hr_ms_interview_assessment.step_order_group', 'ASC')
                ->orderBy('hr_ms_interview_assessment.step_order', 'ASC')            
                ->get()
                ->groupBy('step_order_group');

            foreach ($assessmentData_user as $group) {
                $first = $group->first();
                $assessmentGroupsUser[] = [
                    'assessment_id' => $first->assessment_id,
                    'assessment_group' => $first->assessment_group,
                    'assessment_type' => $first->assessment_type,
                    'selected_score' => $group
                        ->filter(fn($item) => $item->assessment_score_value > 0)
                        ->pluck('assessment_score_value')
                        ->first() ?? 0,
                    'options' => $group->map(function ($item) {
                        return [
                            'assessment_score' => $item->assessment_score,
                            'assessment_descr' => $item->assessment_descr
                        ];
                    })->values()->toArray()
                ];
            }
        }

        $year = now()->year;
        // $photo = 'https://storage.googleapis.com/pkwjkt-attachment/' . $applicant->upload_photo;
        // $cv = 'https://storage.googleapis.com/pkwjkt-attachment/' . $applicant->upload_cv;
        // $coverletter = 'https://storage.googleapis.com/pkwjkt-attachment/' . $applicant->upload_coverletter;

        $config = config('filesystems.disks.gcs');
        // Pastikan StorageClient di-import dan digunakan dengan benar
        $storage = new StorageClient([
            'projectId'   => $config['project_id'],
            'keyFilePath' => $config['key_file'],
        ]);

        $bucket = $storage->bucket($config['bucket']);
        $expiration = \Carbon\Carbon::now()->addMinutes(30);

        $photo = null;
        $cv = null;
        $coverletter = null;

        if (!empty($applicant->upload_photo)) {
            $object = $bucket->object($applicant->upload_photo);
            // signedUrl expects DateTimeInterface
            $photo = $object->signedUrl($expiration);
        }

        if (!empty($applicant->upload_cv)) {
            $object = $bucket->object($applicant->upload_cv);
            $cv = $object->signedUrl($expiration);
        }

        $agenda = Agenda::where('refid', $career->docid)->get();
        $userlist = User::where('status','A')->get();
        $agenda = Agenda::where('refid', $career->docid)->get();

        $typestep = JobApplyStep::leftjoin('hr_ms_job_step', 'hr_trx_job_apply_step.step_id', '=', 'hr_ms_job_step.step_id')                                      
            ->select('hr_trx_job_apply_step.step_id', 'hr_ms_job_step.step_descr')   
            ->where('hr_trx_job_apply_step.docid',$career->docid)       
            ->where('hr_trx_job_apply_step.status','<>','X')
            ->orderBy('hr_trx_job_apply_step.step_order', 'ASC')
            ->get();

        $payrolls = Payrollconfirm::where('jobapply_id', $career->docid)->get();

        $onboarding = Tronboarding::where('jobapply_id', $career->docid)->first();
          
        return view('pages.careers.showcareers', compact(
            'career','applicant','applicant_family','applicant_marital','applicant_education','applicant_working',
            'applicant_language','applicant_course','applicant_sw','applicant_skill','jobapplystep',
            'jobres','jobqua','jobposting','tr_checklist','year','photo','cv','coverletter','user','datenow',
            'assessmentGroups','tr_assessment','tr_assessment_user','assessmentGroupsUser','agenda','userlist','typestep','payrolls','onboarding'
        ));
    }


    public function updateAssessment(Request $request)
    {
        // dd($request->all());
        $user = Auth::user();
        $now = now();
        $docid = $request->docid;

        // Update header (TrAssessment)
        $assessment = TrAssessment::where('jobapply_id', $docid)
            ->where('type','hc')
            ->first();
        // dd($assessment);    
        if ($assessment) {
            $assessment->assessment_date = $request->interview_date.' '.$request->interview_time;        
            $assessment->total_assessment_score_value = $request->totalscore;
            // $assessment->result_status = $request->result_status;
            $assessment->user = $user->username;
            $assessment->updated_user = $user->username;
            $assessment->updated_at = $now;
            $assessment->save();
        }

        // Update detail scores
        foreach ($request->scores as $index => $value) {
            // Reset semua score_value jadi 0 dulu di group ini
            TrAssessmentdetail::where('docid', $assessment->docid)
                ->where('step_order_group', $index + 1)
                ->update([
                    'assessment_score_value' => 0,
                    'updated_user' => $user->username,
                    'updated_at' => $now
                ]);
        
            // Update score_value yang dipilih
            TrAssessmentdetail::where('docid', $assessment->docid)
                ->where('step_order_group', $index + 1)
                ->where('assessment_score', $value)
                ->update([
                    'assessment_score_value' => $value,
                    'updated_user' => $user->username,
                    'updated_at' => $now
                ]);
        }
        

        return response()->json(['success' => true, 'message' => 'Assessment updated successfully']);
    }

    public function updateAssessmentuser(Request $request)
    {
        // dd($request->all());
        $user = Auth::user();
        $now = now();
        $docid = $request->docid;

        // Update header (TrAssessment)
        $assessment = TrAssessment::where('jobapply_id', $docid)
            ->where('type','user')
            ->first();
        // dd($assessment);    
        if ($assessment) {
            $assessment->assessment_date = $request->interview_date.' '.$request->interview_time;        
            $assessment->total_assessment_score_value = $request->totalscore;
            // $assessment->result_status = $request->result_status;
            $assessment->user = $user->username;
            $assessment->updated_user = $user->username;
            $assessment->updated_at = $now;
            $assessment->save();
        }

        // Update detail scores
        foreach ($request->scores as $index => $value) {
            // Reset semua score_value jadi 0 dulu di group ini
            TrAssessmentdetail::where('docid', $assessment->docid)
                ->where('step_order_group', $index + 1)
                ->update([
                    'assessment_score_value' => 0,
                    'updated_user' => $user->username,
                    'updated_at' => $now
                ]);
        
            // Update score_value yang dipilih
            TrAssessmentdetail::where('docid', $assessment->docid)
                ->where('step_order_group', $index + 1)
                ->where('assessment_score', $value)
                ->update([
                    'assessment_score_value' => $value,
                    'updated_user' => $user->username,
                    'updated_at' => $now
                ]);
        }
        

        return response()->json(['success' => true, 'message' => 'Assessment updated successfully']);
    }


    
    public function fetchComments($id)
    {
    
        $comments = T_Message::where('docid', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'comments' => $comments
        ]);
    }
    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:500',
        ]);
        // dd($id);
        $user = request()->user();
        $comment = new T_Message();
        $comment->docid = $id;
        $comment->doctype = 'PRF';
        $comment->username = $user->username; 
        $comment->name = $user->name; 
        $comment->message = $request->comment;
        $comment->status = 'A';
        $comment->created_at = now();
        $comment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment added successfully!',
            'comment' => $comment
        ]);
    }

    public function approveCareer(Request $request, $docid)
    {
        $datestamp = Carbon::now()->toDateTimeString();       
        $user = request()->user(); // Ambil user yang login
        
        $career = Career::where('docid', $docid)->first();  

        if (!$career) {
            return response()->json(['success' => false, 'message' => 'Career not found'], 404);
        }

        $jobposting = Jobposting::where('docid', $career->jobid)->first();

        if (!$jobposting) {
            return response()->json(['success' => false, 'message' => 'Job Posting not found'], 404);
        }

        // Cek apakah user termasuk dalam daftar approval
        $cek_approval = T_approval::where('docid', $jobposting->refid)
            ->where('aprvusername', 'like', '%' . $user->username . '%')
            ->first();

        if (!$cek_approval) {
            return response()->json(['success' => false, 'message' => "You Can't Approve!"], 403);
        }

        $t_approval = JobApplyStep::where('docid', $career->docid)
            ->where('status', 'P')
            ->orderBy('step_order', 'ASC')
            ->first();

        if (!$t_approval) {
            return response()->json(['success' => false, 'message' => 'No pending step to approve'], 404);
        }

    
        if ($t_approval->step_order == 2) {    
            $this->insert_checklist($career, $user);
            $this->insert_assessment($career, $user);   
            $this->insert_psychotest($career, $user);
            $this->sendemail_applicant($career, $user);     
        }


        $t_approval->status = 'A';
        $t_approval->aprvuserdate = $datestamp;
        $t_approval->aprvusername = $user->username;
        $t_approval->save();

        $t_approval_next = JobApplyStep::where('docid', $career->docid)
            ->where('status', 'P')
            ->orderBy('step_order', 'ASC')
            ->first();
       
        if ($t_approval_next) {
            $career->apply_step = $t_approval_next->step_id;
            $career->prev_apply_step = $t_approval->step_id;
        }
        
        $career->updated_user = $user->username;
        $career->updated_at = $datestamp;
        $career->save();

        // Hitung apakah ini adalah approval terakhir
        $count_approval = JobApplyStep::where('docid', $career->docid)
            ->where('status', 'P')
            ->count();

        if ($count_approval === 0) {
            $career->status = 'C';
            $career->apply_step = $t_approval->step_id;
            $career->completed_user = $user->username;
            $career->completed_at = $datestamp;
            $career->save();           
        }

        return response()->json(['success' => true, 'message' => 'Career approved successfully']);
    }
     

    public function rejectCareer(Request $request, $docid)
    {
        $datestamp = Carbon::now()->toDateTimeString();       
        $user = request()->user(); // Ambil user yang login
        
        $career = Career::where('docid', $docid)->first();  

        if (!$career) {
            return response()->json(['success' => false, 'message' => 'Career not found'], 404);
        }

        $jobposting = Jobposting::where('docid', $career->jobid)->first();

        if (!$jobposting) {
            return response()->json(['success' => false, 'message' => 'Job Posting not found'], 404);
        }

        // Cek apakah user termasuk dalam daftar approval
        $cek_approval = T_approval::where('docid', $jobposting->refid)
            ->where('aprvusername', 'like', '%' . $user->username . '%')
            ->get();

        if ($cek_approval->isEmpty()) {
            return response()->json(['success' => false, 'message' => "You Can't Approve!"], 403);
        }

        $t_approval = JobApplyStep::where('docid', $career->docid)
            ->where('status', 'P')
            ->orderBy('step_order', 'ASC')
            ->first();

        if (!$t_approval) {
            return response()->json(['success' => false, 'message' => 'No pending step to approve'], 404);
        }

        $t_approval->status = 'R';
        $t_approval->aprvuserdate = $datestamp;
        $t_approval->aprvusername = $user->username;
        $t_approval->save();

        $career->status = 'R';
        $career->save();

        $t_aprv_sisa = JobApplyStep::where('docid', '=', $career->docid)
            ->where('status', '=', 'P')
            ->get();

        foreach ($t_aprv_sisa as $t_aprv) {
            $t_aprv->status = 'X';
            $t_aprv->save();
        }

        $id = $career->id;
        $doctype ='JAP';
        app('App\Http\Controllers\SendCommentController')->sendmsg($id, $doctype, $request);
    

        return response()->json(['success' => true, 'message' => 'Career rejected successfully']);
    }
       

    public function checkApproval($id, $action)
    {
        $user = Auth::user(); // Ambil user yang login

        $career = Career::where('docid', $id)->first();
        if (!$career) {
            return response()->json(['canPerformAction' => false, 'message' => 'Career not found'], 404);
        }

        $jobposting = Jobposting::where('docid', $career->jobid)->first();
        if (!$jobposting) {
            return response()->json(['canPerformAction' => false, 'message' => 'Job posting not found'], 404);
        }

        // Mulai query approval
        $query = T_approval::where('docid', $jobposting->refid)
            ->where(function ($q) use ($user) {
                $q->where('aprvusername', $user->username)
                ->orWhere('aprvusername', 'like', '%'.$user->username.'%');
            });

        // Tambahan validasi aksi jika perlu
        if (in_array($action, ['approve', 'reject', 'revise'])) {
            // Bisa tambahkan logika lebih detail di sini jika diperlukan
        }

        // Cek apakah username ditemukan
        $canPerformAction = $query->exists();

        return response()->json([
            'canPerformAction' => $canPerformAction,
            'message' => $canPerformAction ? 'Authorized' : 'Unauthorized'
        ]);
    }

    public function insert_checklist($career, $user)
    {
        // dd($career);
    
        DB::beginTransaction();
        try {
            $doctype ='CHK';
            $datenow = Carbon::now()->format('Y-m-d');       
            $datestamp = Carbon::now()->toDateTimeString();   
            $dt = Carbon::now();
            $year = $dt->year;
            $month = str_pad($dt->month, 2, '0', STR_PAD_LEFT);  
            $user = Auth::user();
                        
            $existing = Trchecklist::where('jobid', $career->jobid)
                ->where('applicant_id', $career->applicant_id)
                ->first();
            
            if ($existing) {
                return response()->json([
                'error' => true,
                'message' => 'You have already checklist.'
                ], 409); // Conflict
            }      

            // Generate task ID
            $autonbr = Autonbr::lockForUpdate()
                ->where('doctype', $doctype)
                ->where('year', $year)
                ->where('month', $month)
                ->where('status', 'A')
                ->first();

            if (!$autonbr) {
                $autonbr = Autonbr::create([
                    'doctype' => $doctype,
                    'year' => $year,
                    'month' => $month,
                    'status' => 'A',
                    'number' => 1
                ]);
                $urutan = 1;
            } else {
                $urutan = $autonbr->number + 1;
                $autonbr->number = $urutan;
                $autonbr->save();
            }
            
            $tglbln = substr($year, 2) . $month;
            $docid = $doctype . $tglbln . sprintf("%05d", $urutan);         
                              
            $ms_checklist = Mschecklist::orderby('step_order','ASC')         
                ->get();
                
            foreach ($ms_checklist as $cek) {
                Trchecklist::create([
                    'docid' => $docid,
                    'jobapply_id' => $career->docid,
                    'jobid' => $career->jobid,
                    'applicant_id' => $career->applicant_id,
                    'checklist_id' => $cek->checklist_id,
                    'checklist_type' => $cek->checklist_type,
                    'step_order' => $cek->step_order,
                    'checklist_mandatory' => $cek->checklist_mandatory,
                    'checklist_receive' => 0,                
                    'created_user' => $user->username,
                    'status' => 'P'                                               
                ]);
            }          
     
            DB::commit();
            return response()->json(['success' => true, 'ms_checklist' => $ms_checklist]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan Transaksi Checklist', 'message' => $e->getMessage()], 500);
        }
    }

    public function insert_assessment($career, $user)
    {
        
        DB::beginTransaction();
        try {
            $doctype = 'JOS';
            $dt = Carbon::now();
            $year = $dt->year;
            $month = str_pad($dt->month, 2, '0', STR_PAD_LEFT);  

            $types = ['hc', 'user'];
            $createdDocs = [];

            // Validasi awal
            $existing = TrAssessment::where('jobid', $career->jobid)
                ->where('applicant_id', $career->applicant_id)
                ->whereIn('type', $types)
                ->exists();

            if ($existing) {
                return response()->json([
                    'error' => true,
                    'message' => 'Assessment already exists.'
                ], 409);
            }

            $ms_checklist = MsAssessment::orderBy('step_order_group', 'ASC')
                ->orderBy('step_order', 'ASC')
                ->get();

            if ($ms_checklist->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Master assessment not found.'
                ], 404);
            }

            foreach ($types as $type) {
                // Ambil nomor baru untuk masing-masing type
                $autonbr = Autonbr::lockForUpdate()
                    ->where('doctype', $doctype)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('status', 'A')
                    ->first();

                if (!$autonbr) {
                    $autonbr = Autonbr::create([
                        'doctype' => $doctype,
                        'year' => $year,
                        'month' => $month,
                        'status' => 'A',
                        'number' => 1
                    ]);
                    $urutan = 1;
                } else {
                    $urutan = $autonbr->number + 1;
                    $autonbr->number = $urutan;
                    $autonbr->save();
                }

                $tglbln = substr($year, 2) . $month;
                $docid = $doctype . $tglbln . sprintf("%05d", $urutan);

                // Insert detail
                foreach ($ms_checklist as $cek) {
                    TrAssessmentdetail::create([
                        'docid' => $docid,
                        'jobapply_id' => $career->docid,
                        'jobid' => $career->jobid,
                        'applicant_id' => $career->applicant_id,
                        'assessment_id' => $cek->assessment_id,
                        'step_order_group' => $cek->step_order_group,
                        'step_order' => $cek->step_order,
                        'assessment_type' => $cek->assessment_type,
                        'assessment_score' => $cek->assessment_score,
                        'assessment_score_value' => 0,
                        'created_user' => $user->username,
                        'status' => 'P'
                    ]);
                }

                // Insert header
                TrAssessment::create([
                    'docid' => $docid,
                    'jobapply_id' => $career->docid,
                    'jobid' => $career->jobid,
                    'applicant_id' => $career->applicant_id,
                    'type' => $type,
                    'total_assessment_score_value' => 0,
                    'created_user' => $user->username,
                    'status' => 'P'
                ]);

                $createdDocs[] = $docid;
            }

            DB::commit();
            return response()->json(['success' => true, 'created_docids' => $createdDocs]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to insert assessment.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function insert_jobposting($id)
    {
        
        DB::beginTransaction();
        try {
            $doctype = 'JOB';
            $datenow = Carbon::now()->format('Y-m-d');
            $dt = Carbon::now();
            $year = $dt->year;
            $month = str_pad($dt->month, 2, '0', STR_PAD_LEFT);            
            $datestamp = Carbon::now()->toDateTimeString();
            $user = request()->user();

            // Generate task ID
            $autonbr = AutonbrJobportal::lockForUpdate()
                ->where('doctype', $doctype)
                ->where('year', $year)
                ->where('month', $month)
                ->where('status', 'A')
                ->first();

            if (!$autonbr) {
                $autonbr = AutonbrJobportal::create([
                    'doctype' => $doctype,
                    'year' => $year,
                    'month' => $month,
                    'status' => 'A',
                    'number' => 1
                ]);
                $urutan = 1;
            } else {
                $urutan = $autonbr->number + 1;
                $autonbr->number = $urutan;
                $autonbr->save();
            }

            $tglbln = substr($year, 2) . $month;
            $docid = $doctype . $tglbln . sprintf("%03d", $urutan);

            $career = Career::where('docid', $id)          
                ->first();
                  
            $task = Jobposting::create([
                'docid' => $docid,
                'refid' => $career->docid,
                'cpnyid' => $career->cpnyid,
                'departementid' => $career->departementid,
                'date' => $datenow,
                'job_title' => $career->job_title,
                'job_level' => $career->job_level,                
                'immediate_superior' => $career->immediate_superior,                
                'state_position' => $career->state_position,
                'job_type' => $career->job_type,
                'reason_vacancy' => $career->reason_vacancy,
                'required' => $career->required,
                'actual' => $career->actual,
                'total_actual' => $career->total_actual,       
                'education' => $career->education,
                'experience_start' => $career->experience_start,
                'experience_end' => $career->experience_end,           
                'created_user' => $user->username,
                'status' =>'C'              
            ]);
           
            $jobres = JobResponsiblities::where('docid', $id)          
                ->get();
            
            foreach ($jobres as $jr) {
                JobpostingResponsiblities::create([
                    'docid' => $docid,
                    'refid' => $jr->docid,
                    'no_job_responsiblities' => $jr->no_job_responsiblities,
                    'job_responsibilities_descr' => $jr->job_responsibilities_descr,
                    'created_user' => $jr->created_user,
                    'status' => 'C'                                               
                ]);
            }            

            $jobqua = JobQualification::where('docid', $id)          
                ->get();
            
            foreach ($jobqua as $jq) {
                JobpostingQualification::create([
                    'docid' => $docid,
                    'refid' => $jq->docid,
                    'no_job_qualification' => $jq->no_job_qualification,
                    'job_qualification_descr' => $jq->job_qualification_descr,
                    'created_user' => $jq->created_user,
                    'status' => 'C'                                               
                ]);
            }          
                      
            DB::commit();
            return response()->json(['success' => true, 'task' => $task]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan task', 'message' => $e->getMessage()], 500);
        }
    }


    public function uploadDocument(Request $request)
    {
        $user = request()->user();
        $datestamp = Carbon::now()->toDateTimeString();
        $year = now()->year;

        // dd($request->all());

        // $request->validate([
        //     'checklist_id' => 'required|exists:tr_checklist,id',
        //     'document' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048',
        // ]);

        $document = null;                                    
        
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $randomNumber = random_int(10000000, 99999999);
            $originalName = str_replace('%', '', $file->getClientOriginalName());
            $document = md5($randomNumber) . '-' . $originalName;
        
            $folder_attach = public_path('/attachments/' . $year);
            if (!is_dir($folder_attach)) {
                mkdir($folder_attach, 0777, true);
            }
            $file->move($folder_attach, $document);
        }

        $checklist = Trchecklist::findOrFail($request->checklist_id);
        // dd($checklist);
        $checklist->checklist_filename = $originalName;
        $checklist->checklist_attachfile = $document;
        $checklist->checklist_receive = 1;
        $checklist->checklist_by = $user->username;
        $checklist->checklist_at = $datestamp;
        $checklist->status = 'A';
        $checklist->save();
       

        return response()->json(['success' => true, 'message' => 'Document uploaded']);
    }

    public function sendemail_applicant($career, $user)
    {
        $applicant = Applicant::where('applicant_id', $career->applicant_id)->first();

        $jobapply = JobApply::where('docid',  $career->docid)
            ->where('applicant_id', $applicant->applicant_id)
            ->first();

        $jobapply->status = 'P';
        $jobapply->save();

        if (!$applicant || empty($applicant->email_address)) {
            return response()->json(['error' => 'Applicant email not found.'], 404);
        }

        $encryptedDocId = Crypt::encryptString($career->applicant_id);

        $data = [
            'name' => $applicant->full_name ?? 'Pelamar',
            // 'url' => url('http://careerjakarta.pakuwon.local/checkform') // gunakan URL lengkap
            'url'  => url("http://careerjakarta.pakuwon.local/checkform/{$encryptedDocId}")
        ];

        Mail::send('emails.mailapplicant', $data, function ($message) use ($applicant,$data) {
            $message->to($applicant->email_address)
                    ->subject('📩 Lengkapi Aplikasi Anda di Pakuwon Career');
            $message->from('digitalserver@pakuwon.com', 'Pakuwon Career');
        });

        return response()->json(['success' => 'Email has been sent to applicant.']);
    }

    public function checkRejectPermission($docid)
    {
        $user = Auth::user();

        // Cek apakah user punya hak reject pada langkah saat ini
        $step = JobApplyStep::where('docid', $docid)
            ->where('status', 'P')           
            ->orderBy('step_order','ASC')    
            ->first();  


        if ($step) {
            $canReject = str_contains($step->step_approve, 'Reject');
            return response()->json(['canReject' => $canReject]);
        }

        return response()->json(['canReject' => false]);
    }

    public function generatePayroll(Request $request)
    {
        // dd($request->all());
        // $employee = \DB::table('employees')->where('id', $request->employee_id)->first();
        $applicant = Applicant::where('applicant_id', $request->applicant_id)->first();
        $company = Company::where('cpnyid', $request->cpnyid)->first();

        $templatePath = storage_path('app/templates/PayrollConfirmation.docx');
        $tempDocPath = storage_path('app/temp_filled.docx');
        $datebirth = Carbon::parse($applicant->date_of_birth)->translatedFormat('d F Y');
        $templateProcessor = new TemplateProcessor($templatePath);
       
        // Set placeholder (harus cocok dengan yang di dalam DOCX)
        $templateProcessor->setValue('full_name', $applicant->full_name);
        $templateProcessor->setValue('gender', $applicant->gender);
        $templateProcessor->setValue('birth_place', $applicant->birth_place);
        $templateProcessor->setValue('datebirth', $datebirth);
        $templateProcessor->setValue('religion', $applicant->religion);
        $templateProcessor->setValue('ktp_id', $applicant->ktp_id);
        $templateProcessor->setValue('cpnyid', $company->cpnyname);
        $templateProcessor->setValue('departementid', $request->departementid);
        $templateProcessor->setValue('job_title', $request->job_title);
        $templateProcessor->setValue('job_level', $request->job_level);

        // $templateProcessor->setValue('salary', number_format($employee->salary, 2));
        // $templateProcessor->setValue('date', now()->format('d M Y'));

        $templateProcessor->saveAs($tempDocPath);

        // Convert DOCX to HTML (basic method)
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempDocPath);
        $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        ob_start();
        $htmlWriter->save('php://output');
        $htmlContent = ob_get_clean();

        $pdf = PDF::loadHTML($htmlContent)->setPaper('A4', 'portrait');
        return $pdf->download('payroll-confirmation.pdf');
    }
   
    public function generateOffering(Request $request)
    {
        // dd($request->all());
        // $employee = \DB::table('employees')->where('id', $request->employee_id)->first();
        $applicant = Applicant::where('applicant_id', $request->applicant_id)->first();
        $company = Company::where('cpnyid', $request->cpnyid)->first();

        $templatePath = storage_path('app/templates/PayrollConfirmation.docx');
        $tempDocPath = storage_path('app/temp_filled.docx');
        $datebirth = Carbon::parse($applicant->date_of_birth)->translatedFormat('d F Y');
        $templateProcessor = new TemplateProcessor($templatePath);
       
        // Set placeholder (harus cocok dengan yang di dalam DOCX)
        $templateProcessor->setValue('full_name', $applicant->full_name);
        $templateProcessor->setValue('gender', $applicant->gender);
        $templateProcessor->setValue('birth_place', $applicant->birth_place);
        $templateProcessor->setValue('datebirth', $datebirth);
        $templateProcessor->setValue('religion', $applicant->religion);
        $templateProcessor->setValue('ktp_id', $applicant->ktp_id);
        $templateProcessor->setValue('cpnyid', $company->cpnyname);
        $templateProcessor->setValue('departementid', $request->departementid);
        $templateProcessor->setValue('job_title', $request->job_title);
        $templateProcessor->setValue('job_level', $request->job_level);
        $templateProcessor->setValue('date', now()->format('d M Y'));
        // $templateProcessor->setValue('salary', number_format($employee->salary, 2));
        

        $templateProcessor->saveAs($tempDocPath);

        // Convert DOCX to HTML (basic method)
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempDocPath);
        $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        ob_start();
        $htmlWriter->save('php://output');
        $htmlContent = ob_get_clean();

        $pdf = PDF::loadHTML($htmlContent)->setPaper('A4', 'portrait');
        return $pdf->download('payroll-confirmation.pdf');
    }
    
    public function pdfPayrollconfirmation(Request $request)
    {
        // dd($request->all());
        $applicant = Applicant::where('applicant_id', $request->applicant_id)->first();
        $company = Company::select(['cpnyid', 'cpnyname'])->where('cpnyid', $request->cpnyid)->first();
        $payrollconfirm = Payrollconfirm::where('applicant_id', $request->applicant_id)->first();
        $t_approval = T_approval::where('docid', $request->refid)
            ->orderby('aprvid','ASC')
            ->get();
        // dd($t_approval);    
        $data = [
            'cpnyid' => $company->cpnyname,
            'departementid' => $request->departementid,
            'full_name' => $applicant->full_name,
            'gender' => $applicant->gender,
            'birth_place' => $applicant->birth_place,
            'martial_status' => $applicant->martial_status,
            'datebirth' => \Carbon\Carbon::parse($applicant->date_of_birth)->format('d F Y'),
            'religion' => $applicant->religion,
            'ktp_id' => $applicant->ktp_id,
            'job_title' => $request->job_title,
            'job_level' => $request->job_level,
            'tax_liability' => $payrollconfirm->tax_liability ?? '-',
            'npwp_id' => $payrollconfirm->npwp_id ?? '-',
            'bank_account' => $payrollconfirm->bank_account ?? '-',
            'bank_name' => $payrollconfirm->bank_name ?? '-',
            'net_salary' => $payrollconfirm->net_salary ?? '0',
            'other_facility' => $payrollconfirm->other_facility ?? '-',
            'availability_date' => $payrollconfirm->availability_date ?? '-',
            'work_start_date' => $payrollconfirm->work_start_date ?? '-',
            'employment_status' => $payrollconfirm->employment_status ?? '-', 
            'approvals' => $t_approval,
        ];

        return Pdf::loadView('pages.careers.pdfpayroll', $data)
            ->setPaper('a4')
            ->stream('payroll-confirmation.pdf');
    }


    public function pdfOfferingletter(Request $request)
    {
        // dd($request->all());
       
        $applicant = Applicant::where('applicant_id', $request->applicant_id)->first();
        $company = Company::where('cpnyid', $request->cpnyid)->first();
        $datebirth = Carbon::parse($applicant->date_of_birth)->translatedFormat('d F Y');   
        $payrollconfirm = Payrollconfirm::where('applicant_id', $request->applicant_id)->first();    
         
        $net_salary = $payrollconfirm->net_salary ?? 0;
        $salary_words = terbilang($net_salary) . ' rupiah';


        $data = [
            'cpnyid' => $company->cpnyname,
            'departementid' => $request->departementid,
            'full_name' => $applicant->full_name,
            'gender' => $applicant->gender,
            'birth_place' => $applicant->birth_place,
            'martial_status' => $applicant->martial_status,            
            'datebirth' => $datebirth,
            'religion' => $applicant->religion,
            'ktp_id' => $applicant->ktp_id,
            'job_title' => $request->job_title,
            'job_level' => $request->job_level,
            'date' => now()->format('d F Y'),    
            'net_salary' => number_format($net_salary) ?? '0',
            'salary_words' => $salary_words,          
            'logo' => $company->cpnyid,
                    
        ];

        $pdf = PDF::loadview('pages.careers.pdfofferingletter', $data);
        return $pdf->stream('offering-letter.pdf');
        
    }

    public function pdfPaktaintegritas(Request $request)
    {
        // dd($request->all());
       
        $applicant = Applicant::where('applicant_id', $request->applicant_id)->first();
        $company = Company::where('cpnyid', $request->cpnyid)->first();
        $datebirth = Carbon::parse($applicant->date_of_birth)->translatedFormat('d F Y');

        $data = [
            'cpnyid' => $company->cpnyname,
            'departementid' => $request->departementid,
            'full_name' => $applicant->full_name,    
            'job_title' => $request->job_title,      
            'date' => now()->format('d F Y'),     
        ];

        $pdf = PDF::loadview('pages.careers.pdfpaktaintegritas', $data);
        return $pdf->stream('pakta-integritas.pdf');
        
    }

    public function pdfPernyataanelectonik(Request $request)
    {
        // dd($request->all());
       
        $applicant = Applicant::where('applicant_id', $request->applicant_id)->first();
        $company = Company::where('cpnyid', $request->cpnyid)->first();
        $datebirth = Carbon::parse($applicant->date_of_birth)->translatedFormat('d F Y');

        $data = [
            'cpnyid' => $company->cpnyname,           
            'full_name' => $applicant->full_name,           
            'ktp_id' => $applicant->ktp_id,
            'id_address' => $applicant->id_address,           
            'date' => now()->format('d F Y'),     
        ];

        $pdf = PDF::loadview('pages.careers.pdfpernyataanelectronik', $data);
        return $pdf->stream('pernyataan-electronik.pdf');
        
    }

    public function insert_psychotest($career, $user)
    {
        
        DB::beginTransaction();
        try {
            $doctype = 'JPS';
            $dt = Carbon::now();
            $year = $dt->year;
            $month = str_pad($dt->month, 2, '0', STR_PAD_LEFT);  

            $types = ['manager', 'staff'];
            $createdDocs = [];

            // Validasi awal
            $existing = TrAssessment::where('jobid', $career->jobid)
                ->where('applicant_id', $career->applicant_id)
                ->whereIn('type', $types)
                ->exists();

            if ($existing) {
                return response()->json([
                    'error' => true,
                    'message' => 'Assessment already exists.'
                ], 409);
            }

            $ms_checklist = MsAssessment::orderBy('step_order_group', 'ASC')
                ->orderBy('step_order', 'ASC')
                ->get();

            if ($ms_checklist->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Master assessment not found.'
                ], 404);
            }

            foreach ($types as $type) {
                // Ambil nomor baru untuk masing-masing type
                $autonbr = Autonbr::lockForUpdate()
                    ->where('doctype', $doctype)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('status', 'A')
                    ->first();

                if (!$autonbr) {
                    $autonbr = Autonbr::create([
                        'doctype' => $doctype,
                        'year' => $year,
                        'month' => $month,
                        'status' => 'A',
                        'number' => 1
                    ]);
                    $urutan = 1;
                } else {
                    $urutan = $autonbr->number + 1;
                    $autonbr->number = $urutan;
                    $autonbr->save();
                }

                $tglbln = substr($year, 2) . $month;
                $docid = $doctype . $tglbln . sprintf("%05d", $urutan);

                // Insert detail
                foreach ($ms_checklist as $cek) {
                    TrAssessmentdetail::create([
                        'docid' => $docid,
                        'jobapply_id' => $career->docid,
                        'jobid' => $career->jobid,
                        'applicant_id' => $career->applicant_id,
                        'assessment_id' => $cek->assessment_id,
                        'step_order_group' => $cek->step_order_group,
                        'step_order' => $cek->step_order,
                        'assessment_type' => $cek->assessment_type,
                        'assessment_score' => $cek->assessment_score,
                        'assessment_score_value' => 0,
                        'created_user' => $user->username,
                        'status' => 'P'
                    ]);
                }

                // Insert header
                TrAssessment::create([
                    'docid' => $docid,
                    'jobapply_id' => $career->docid,
                    'jobid' => $career->jobid,
                    'applicant_id' => $career->applicant_id,
                    'type' => $type,
                    'total_assessment_score_value' => 0,
                    'created_user' => $user->username,
                    'status' => 'P'
                ]);

                $createdDocs[] = $docid;
            }

            DB::commit();
            return response()->json(['success' => true, 'created_docids' => $createdDocs]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to insert assessment.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function storePayroll(Request $request)
    {
        $user = Auth::user();

        $career = Career::where('docid', $request->jobapply_id)->first();

        // Validasi jika career tidak ditemukan
        if (!$career) {
            return response()->json([
                'error' => true,
                'message' => 'Data career tidak ditemukan.'
            ], 404);
        }

        // Cek apakah onboarding sudah ada
        $existing = Tronboarding::where('jobapply_id', $career->docid)
            ->where('applicant_id', $career->applicant_id)
            ->where('jobid', $career->jobid)
            ->exists();

        if ($existing) {
            return response()->json([
                'error' => true,
                'message' => 'Transaksi onboarding sudah ada.'
            ], 409);
        }

        // Lanjut insert onboarding        
        try {
            $this->insert_onboarding($career, $user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 409);
        }


        DB::beginTransaction();
        try {
            $doctype = 'OFF';
            $datenow = Carbon::now()->format('Y-m-d');
            $now = Carbon::now();
            $year = $now->year;
            $month = str_pad($now->month, 2, '0', STR_PAD_LEFT);

            // Cek apakah payroll sudah ada
            $payrollExists = Payrollconfirm::where('jobapply_id', $career->docid)
                ->where('applicant_id', $career->applicant_id)
                ->where('jobid', $career->jobid)
                ->exists();

            if ($payrollExists) {
                return response()->json([
                    'error' => true,
                    'message' => 'Payroll untuk kandidat ini sudah ada.'
                ], 409);
            }

            // Ambil atau buat nomor urut dokumen
            $autonbr = Autonbr::lockForUpdate()
                ->where('doctype', $doctype)
                ->where('year', $year)
                ->where('month', $month)
                ->where('status', 'A')
                ->first();

            if (!$autonbr) {
                $autonbr = Autonbr::create([
                    'doctype' => $doctype,
                    'year' => $year,
                    'month' => $month,
                    'status' => 'A',
                    'number' => 1
                ]);
                $urutan = 1;
            } else {
                $urutan = $autonbr->number + 1;
                $autonbr->update(['number' => $urutan]);
            }

            $tglbln = substr($year, 2) . $month;
            $docid = $doctype . $tglbln . sprintf("%05d", $urutan);

            // Simpan payroll
            Payrollconfirm::create([
                'docid' => $docid,
                'jobapply_id' => $career->docid,
                'jobid' => $career->jobid,
                'applicant_id' => $career->applicant_id,
                'offer_date' => $datenow,
                'tax_liability' => $request->tax_liability,
                'npwp_id' => $request->npwp_id,
                'bank_account' => $request->bank_account,
                'bank_name' => $request->bank_name,
                'gross_salary' => $request->net_salary,
                'net_salary' => $request->net_salary,
                'other_facility' => $request->other_facility,
                'availability_date' => $request->availability_date,
                'work_start_date' => $request->work_start_date,
                'employment_status' => $request->employment_status,
                'status' => 'P',
                'created_user' => $user->username,
            ]);

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Gagal menyimpan Transaksi Payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function editPayroll($id)
    {
        $data = Payrollconfirm::find($id);
        return response()->json($data);
    }

    public function updatePayroll(Request $request)
    {
        $user = Auth::user();
        $payroll = Payrollconfirm::where('applicant_id', $request->applicant_id)          
            ->first();
        
        $payroll->tax_liability = $request->tax_liability;
        $payroll->npwp_id = $request->npwp_id;
        $payroll->bank_account = $request->bank_account;
        $payroll->bank_name = $request->bank_name;        
        $payroll->net_salary = $request->net_salary;
        $payroll->other_facility = $request->other_facility;
        $payroll->availability_date = $request->availability_date;
        $payroll->work_start_date = $request->work_start_date;
        $payroll->employment_status = $request->employment_status;
        $payroll->updated_user = $user->username;
        $payroll->save();          

        return response()->json(['success' => true]);
    }

    public function insert_onboarding($career, $user)
    {
        // dd($career);
    
        DB::beginTransaction();
        try {
            $doctype ='ONB';
            $datenow = Carbon::now()->format('Y-m-d');       
            $datestamp = Carbon::now()->toDateTimeString();   
            $dt = Carbon::now();
            $year = $dt->year;
            $month = str_pad($dt->month, 2, '0', STR_PAD_LEFT);  
            $user = Auth::user();
                      
          
            $existing = Tronboarding::where('jobapply_id', $career->docid)
                ->where('applicant_id', $career->applicant_id)
                ->where('jobid', $career->jobid)
                ->exists();
            
            if ($existing) {
                return response()->json([
                'error' => true,
                'message' => 'You have already onboarding.'
                ], 409); // Conflict
            }      

            // Generate task ID
            $autonbr = Autonbr::lockForUpdate()
                ->where('doctype', $doctype)
                ->where('year', $year)
                ->where('month', $month)
                ->where('status', 'A')
                ->first();

            if (!$autonbr) {
                $autonbr = Autonbr::create([
                    'doctype' => $doctype,
                    'year' => $year,
                    'month' => $month,
                    'status' => 'A',
                    'number' => 1
                ]);
                $urutan = 1;
            } else {
                $urutan = $autonbr->number + 1;
                $autonbr->number = $urutan;
                $autonbr->save();
            }
            
            $tglbln = substr($year, 2) . $month;
            $docid = $doctype . $tglbln . sprintf("%05d", $urutan);         
                              
            $ms_onboarding = Msonboarding::orderby('step_order','ASC')         
                ->get();
               
            foreach ($ms_onboarding as $cek) {
                Tronboarding::create([
                    'docid' => $docid,
                    'jobapply_id' => $career->docid,
                    'jobid' => $career->jobid,
                    'applicant_id' => $career->applicant_id,
                    'checklist_id' => $cek->checklist_onboarding_id,
                    'checklist_type' => $cek->checklist_onboarding_type,
                    'step_order' => $cek->step_order,
                    'checklist_onboarding_mandatory' => $cek->checklist_onboarding_mandatory,
                    'checklist_onboarding_filename' => '',
                    'checklist_onboarding_attachfile' => '',                   
                    'checklist_onboarding_by' => '',                                                     
                    'created_user' => $user->username,
                    'status' => 'P'                                               
                ]);
            }          
     
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // lempar balik supaya controller bisa tangani
        }
    }

    public function getChecklist($docid_onboarding)
    {      
        $checklists = Tronboarding::leftjoin('hr_ms_onboarding_checklist', 'hr_trx_onboarding_checklist.checklist_id', '=', 'hr_ms_onboarding_checklist.checklist_onboarding_id')                                      
            ->select('hr_trx_onboarding_checklist.*', 'hr_ms_onboarding_checklist.checklist_onboarding_descr')   
            ->where('hr_trx_onboarding_checklist.docid',$docid_onboarding)      
            ->orderBy('hr_trx_onboarding_checklist.step_order', 'ASC')
            ->get();

        return response()->json($checklists);
    }

    
    public function updateChecklist(Request $request)
    {
        try {
            $ids = $request->input('checked', []);
            $docid = $request->docid_onboarding;
            $user = Auth::user();

            if (!$docid) {
                return response()->json(['error' => 'DocID kosong!'], 422);
            }

            // Reset semua checklist ke 0 dan kosongkan updated_user
            Tronboarding::where('docid', $docid)
                ->update([
                    'checklist_onboarding_receive' => 0,
                    'updated_user' => $user->username ?? 'system'
                ]);

            // Set checklist yang dipilih ke 1 dan update updated_user
            if (!empty($ids)) {
                Tronboarding::whereIn('id', $ids)
                    ->update([
                        'checklist_onboarding_receive' => 1,
                        'updated_user' => $user->username ?? 'system'
                    ]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Gagal update checklist: '.$e->getMessage());
            return response()->json(['error' => 'Terjadi error: '.$e->getMessage()], 500);
        }
    }

    public function pdfApplicantprofile(Request $request)
    {
        // dd($request->all());
       
        $applicant = Applicant::where('applicant_id', $request->applicant_id)->first();
        $company = Company::where('cpnyid', $request->cpnyid)->first();
        $datebirth = Carbon::parse($applicant->date_of_birth)->translatedFormat('d F Y');

        $year = now()->year;        
        // $photo = 'https://storage.googleapis.com/pkwjkt-attachment/' . $applicant->upload_photo;
        
        $config = config('filesystems.disks.gcs');
        // Pastikan StorageClient di-import dan digunakan dengan benar
        $storage = new StorageClient([
            'projectId'   => $config['project_id'],
            'keyFilePath' => $config['key_file'],
        ]);

        $bucket = $storage->bucket($config['bucket']);
        $expiration = \Carbon\Carbon::now()->addMinutes(30);

        $photo = null;
        
        if (!empty($applicant->upload_photo)) {
            $object = $bucket->object($applicant->upload_photo);
            // signedUrl expects DateTimeInterface
            $photo = $object->signedUrl($expiration);
        }

        $applicant_family = ApplicantFamily::where('applicant_id', $applicant->applicant_id)->get();       
        $applicant_marital = ApplicantMarital::where('applicant_id', $applicant->applicant_id)->get();
        $applicant_education = ApplicantEducation::where('applicant_id', $applicant->applicant_id)->orderBy('id', 'asc')->get();
        $applicant_working = ApplicantWorking::where('applicant_id', $applicant->applicant_id)->get();
        $applicant_language = ApplicantLanguage::where('applicant_id', $applicant->applicant_id)->get();
        $applicant_course = ApplicantCourse::where('applicant_id', $applicant->applicant_id)->get();
        $applicant_sw = ApplicantSW::where('applicant_id', $applicant->applicant_id)->orderBy('sw_type', 'asc')->get();
        $applicant_skill = ApplicantSkill::where('applicant_id', $applicant->applicant_id)->get();

        $data = [
            'cpnyid' => $company->cpnyname,
            'departementid' => $request->departementid,
            'full_name' => $applicant->full_name,    
            'job_title' => $request->job_title,      
            'date' => now()->format('d F Y'),    
            'photo' =>  $photo,
            'applicant' => $applicant,
            'applicant_family' => $applicant_family,
            'applicant_marital' => $applicant_marital,
            'applicant_education' => $applicant_education,
            'applicant_working' => $applicant_working,
            'applicant_language' => $applicant_language,
            'applicant_course' => $applicant_course,
            'applicant_skill' => $applicant_skill,
            'applicant_sw' => $applicant_sw,
        ];        

        $pdf = PDF::loadView('pages.careers.pdfapplicantprofile', $data)
          ->setPaper('A4', 'portrait') // A4 ukuran standar, bisa diganti 'landscape' jika perlu
          ->setOptions(['isRemoteEnabled' => true]);

        return $pdf->stream('applicant-profile.pdf');
        
    }



    
}
