<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswer;
use App\Http\Resources\SurveyResource;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

use App\Enum\QuestionTypeEnum;

class SurveyController extends Controller
{
    // protected $relativePath;
    public function index(Request $request)
    {
        $user  = $request->user();
        return SurveyResource::collection(
            Survey::where("user_id",$user->id)
            ->orderBy("created_at","desc")
            ->paginate(2)
        );
    }
    public function store(StoreSurveyRequest $request)
    {
        $data = $request->validated();
        if(isset($data["image"])){
            $dir = 'images/';
            $imageName = time().'.'.$request->file('image')->extension();
            $file = Str::random() . '.'.$imageName ;
            $absolutePath = public_path($dir);
            $relativePath = $dir . $file;
            // if (!File::exists($absolutePath)) {
                // File::makeDirectory($absolutePath, 0755, true);
            // }
            // file_put_contents($relativePath, $request->file("image"));
            $request->file("image")->move(public_path('images'), $file);
            // $relativePath = $this->saveImage($request);
            $data["image"] = $relativePath;
        }
        $survey = Survey::create($data);
        if(isset($data["questions"])){
            foreach ($data['questions'] as $question) {
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }
        }

        return new SurveyResource($survey);

    }

    public function update(UpdateSurveyRequest $request,Survey $survey)
    {   
        $data = $request->validated();
        if(isset($data["image"])){
            $dir = 'images/';
            $imageName = time().'.'.$request->file('image')->extension();
            $file = Str::random() . '.'.$imageName ;
            $absolutePath = public_path($dir);
            $relativePath = $dir . $file;
            $request->file("image")->move(public_path('images'), $file);
            $data["image"] = $relativePath;
            if ($survey->image) {
                $absolutePath = public_path($survey->image);
                File::delete($absolutePath);
            }
        }
        $survey->update($data);
        // Get ids as plain array of existing questions
        $existingIds = $survey->questions()->pluck('id')->toArray();
        // Get ids as plain array of new questions
        $newIds = Arr::pluck($data['questions'], 'id');
        // Find questions to delete
        $toDelete = array_diff($existingIds, $newIds);
        //Find questions to add
        $toAdd = array_diff($newIds, $existingIds);

        // Delete questions by $toDelete array
        SurveyQuestion::destroy($toDelete);

        // Create new questions
        foreach ($data['questions'] as $question) {
            if (in_array($question['id'], $toAdd)) {
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }
        }

        // Update existing questions
        $questionMap = collect($data['questions'])->keyBy('id');
        foreach ($survey->questions as $question) {
            if (isset($questionMap[$question->id])) {
                $this->updateQuestion($question, $questionMap[$question->id]);
            }
        }
        return new SurveyResource($survey);

    }
    private function createQuestion($data)
    {
        if (is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }
        $validator = Validator::make($data, [
            'question' => 'required|string',
            'type' => [
                'required', new Enum(QuestionTypeEnum::class)
            ],
            'description' => 'nullable|string',
            'data' => 'present',
            'survey_id' => 'exists:App\Models\Survey,id'
        ]);

        return SurveyQuestion::create($validator->validated());
    }

    public function show(Survey $survey,Request $request)
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action');
        }
        return new SurveyResource($survey);
    }
     public function destroy(Survey $survey,Request $request)
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action.');
        }
        $survey->delete();
        // If there is an old image, delete it
        if ($survey->image) {
            $absolutePath = public_path($survey->image);
            File::delete($absolutePath);
        }
        return response('', 204);
    }
    public function getBySlug(Survey $survey)
    {
        if (!$survey->status) {
            return response("", 404);
        }

        $currentDate = new \DateTime();
        $expireDate = new \DateTime($survey->expire_date);
        if ($currentDate > $expireDate) {
            return response("", 404);
        }

        return new SurveyResource($survey);
    }
    private function updateQuestion(SurveyQuestion $question,$data)
    {
        if (is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }
        $validator = Validator::make($data, [
            'id' => 'exists:App\Models\SurveyQuestion,id',
            'question' => 'required|string',
            'type' => ['required', new Enum(QuestionTypeEnum::class)],
            'description' => 'nullable|string',
            'data' => 'present',
        ]);
        return $question->update($validator->validated());
    }



}