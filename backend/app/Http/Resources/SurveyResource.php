<?php

namespace App\Http\Resources;

use App\Models\SurveyQuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
class SurveyResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image_url' => $this->image ? URL::to($this->image) : null,
            'status' => !!$this->status,
            'description' => $this->description,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'expire_date' => (new \DateTime($this->expire_date))->format('Y-m-d'),
            'questions' => SurveyQuestionAnswer::collection($this->questions)
        ];
    }
}
