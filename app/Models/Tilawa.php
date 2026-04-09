<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Tilawa extends Model {
    use HasFactory;
    protected $table='tilawat';
    protected $fillable=['qari_id','title','slug','description','recorded_at','recorded_place','audio_path','duration','cover_image','uploaded_by','is_featured','downloads_count','likes_count','status'];
    protected $casts=['is_featured'=>'boolean','recorded_at'=>'date'];
    public function qari()         { return $this->belongsTo(Qari::class); }
    public function uploader()     { return $this->belongsTo(User::class,'uploaded_by'); }
    public function likes()        { return $this->hasMany(Like::class); }
    public function savedByUsers() { return $this->hasMany(SavedTilawa::class); }
    public function getCoverUrlAttribute(): string {
        if($this->cover_image) return asset('storage/'.$this->cover_image);
        return $this->relationLoaded('qari')&&$this->qari ? $this->qari->image_url : asset('images/default-cover.jpg');
    }
    public function getAudioUrlAttribute(): string { return asset('storage/'.$this->audio_path); }
    public function getFormattedDurationAttribute(): string { $m=floor($this->duration/60); $s=$this->duration%60; return sprintf('%d:%02d',$m,$s); }
    public function getRouteKeyName(): string { return 'slug'; }
}
