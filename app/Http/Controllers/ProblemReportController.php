<?php

namespace App\Http\Controllers;

use App\Models\ProblemReport;
use App\Models\ProblemReportImages;
use App\Models\ProblemReportTopic;
use App\Models\ProblemReportMaster;
use App\Models\member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProblemReportController extends Controller
{
    public function getList($id)
    {
        $items = ProblemReport::where('member_id',$id)->get()->toArray();

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                $items[$key]['No'] = $key + 1;
                $items[$key]['images'] = ProblemReportImages::where('problem_report_id',$items[$key]['id'])->get();
                foreach ($items[$key]['images'] as $key2 => $value2) {
                    if($items[$key]['images'][$key2]['image'])
                    $items[$key]['images'][$key2]['image'] = url($items[$key]['images'][$key2]['image']);
                }

                $items[$key]['topic'] = ProblemReportTopic::find($items[$key]['problem_report_topic_id']);
                $items[$key]['master'] = ProblemReportMaster::find($items[$key]['problem_report_master_id']);
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $items);
    }

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $status = $request->status;
        $member_id = $request->member_id;

        $col = ['id', 'member_id','problem_report_topic_id', 'problem_report_master_id', 'name', 'description','status','response', 'create_by', 'update_by', 'created_at', 'updated_at'];
        $orderby = ['', 'member_id', 'problem_report_topic_id', 'problem_report_master_id', 'name', 'status','response','description', 'create_by', 'update_by', 'created_at', 'updated_at'];

        $query = ProblemReport::select($col);

        if (isset($status)) {
            $query->where('status', $status);
        }

        if (isset($member_id)) {
            $query->where('member_id', $member_id);
        }

        if ($orderby[$order[0]['column']] ?? false) {
            $query->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if (!empty($search['value'])) {
            $query->where(function ($q) use ($search, $col) {
                foreach ($col as $c) {
                    $q->orWhere($c, 'like', '%' . $search['value'] . '%');
                }
            });
        }

        $data = $query->paginate($length, ['*'], 'page', $page);

        if ($data->isNotEmpty()) {
            $No = (($page - 1) * $length);
            foreach ($data as $item) {
                $item->No = ++$No;
                $item->images = ProblemReportImages::where('problem_report_id', $item->id)->get();
                foreach ($item->images as $key => $value) {
                    if($item->images[$key]->image){
                        $item->images[$key]->image = url($item->images[$key]->image);
                    }
                }
                $item->topic = ProblemReportTopic::find($item->problem_report_topic_id);
                $item->master = ProblemReportMaster::find($item->problem_report_master_id);
                $item->member = member::find($item->member_id);
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'problem_report_topic_id' => 'required|integer|exists:problem_report_topics,id',
            'problem_report_master_id' => 'required|integer|exists:problem_report_masters,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string|max:250'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $report = new ProblemReport();
            $report->member_id = $request->member_id;
            $report->problem_report_topic_id = $request->problem_report_topic_id;
            $report->problem_report_master_id = $request->problem_report_master_id;
            $report->name = $request->name;
            $report->description = $request->description;
            $report->save();

            if ($request->has('images') && is_array($request->images)) {
                foreach ($request->images as $image) {
                    $reportImage = new ProblemReportImages();
                    $reportImage->problem_report_id = $report->id;
                    $reportImage->image = $image;
                    $reportImage->save();
                }
            }

            DB::commit();

            return $this->returnSuccess('เพิ่มข้อมูลสำเร็จ', $report);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'problem_report_topic_id' => 'required|integer|exists:problem_report_topics,id',
            'problem_report_master_id' => 'required|integer|exists:problem_report_masters,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string|max:250'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorData($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $report = ProblemReport::find($id);
            if (!$report) {
                return $this->returnErrorData('ไม่พบข้อมูล', 404);
            }
            $report->member_id = $request->member_id;
            $report->problem_report_topic_id = $request->problem_report_topic_id;
            $report->problem_report_master_id = $request->problem_report_master_id;
            $report->name = $request->name;
            $report->description = $request->description;
            $report->save();

            if ($request->has('images') && is_array($request->images)) {
                ProblemReportImages::where('problem_report_id', $id)->delete();

                foreach ($request->images as $image) {
                    $reportImage = new ProblemReportImages();
                    $reportImage->problem_report_id = $report->id;
                    $reportImage->image = $image;
                    $reportImage->save();
                }
            }

            DB::commit();

            return $this->returnSuccess('อัปเดตข้อมูลสำเร็จ', $report);
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $report = ProblemReport::find($id);
            if (!$report) {
                return $this->returnErrorData('ไม่พบข้อมูล', 404);
            }

            ProblemReportImages::where('problem_report_id', $id)->delete();
            $report->delete();

            DB::commit();

            return $this->returnSuccess('ลบข้อมูลสำเร็จ');
        } catch (\Throwable $e) {
            DB::rollback();
            return $this->returnErrorData('เกิดข้อผิดพลาด ' . $e->getMessage(), 500);
        }
    }
}
