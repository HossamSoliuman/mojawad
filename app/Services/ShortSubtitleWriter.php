<?php

namespace App\Services;

class ShortSubtitleWriter
{
    /**
     * Build the ASS script burned into an auto short: the qari and surah at
     * the top, the brand at the bottom, and the recited ayat as timed cues in
     * the middle. libass shapes the Arabic; the fonts ship in resources/fonts.
     *
     * @param  list<array{start: float, end: float, text: string}>  $cues
     */
    public function ass(array $cues, string $qari, string $surah, float $duration, string $brand = 'mojawad.com'): string
    {
        $lines = [
            '[Script Info]',
            'ScriptType: v4.00+',
            'PlayResX: '.(int) config('clips.width'),
            'PlayResY: '.(int) config('clips.height'),
            'WrapStyle: 0',
            'ScaledBorderAndShadow: yes',
            '',
            '[V4+ Styles]',
            'Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding',
            'Style: Ayah,Amiri Quran,140,&H00FFFFFF,&H00FFFFFF,&H50000000,&H8C000000,0,0,0,0,100,100,0,0,1,3,4,5,90,90,0,1',
            'Style: Qari,Aref Ruqaa,92,&H004FC9E8,&H004FC9E8,&H64000000,&H8C000000,1,0,0,0,100,100,0,0,1,2,3,8,70,70,200,1',
            'Style: Surah,Tajawal Medium,58,&H00F0F0F0,&H00F0F0F0,&H64000000,&H8C000000,0,0,0,0,100,100,0,0,1,2,2,8,70,70,345,1',
            'Style: Brand,Tajawal,40,&H30FFFFFF,&H30FFFFFF,&H64000000,&H8C000000,1,0,0,0,100,100,0,0,1,1,2,2,70,70,160,1',
            '',
            '[Events]',
            'Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text',
            $this->event('Qari', 0, $duration, '{\fad(400,0)}'.$this->clean($qari)),
            $this->event('Surah', 0, $duration, '{\fad(400,0)}'.$this->clean($surah)),
            $this->event('Brand', 0, $duration, $this->clean($brand)),
        ];

        foreach ($cues as $cue) {
            $lines[] = $this->event('Ayah', $cue['start'], $cue['end'], '{\fad(220,180)}'.$this->clean($cue['text']));
        }

        return implode("\n", $lines)."\n";
    }

    private function event(string $style, float $start, float $end, string $text): string
    {
        return 'Dialogue: 0,'.$this->time($start).','.$this->time($end).','.$style.',,0,0,0,,'.$text;
    }

    /**
     * ASS timestamps are H:MM:SS.cc.
     */
    private function time(float $seconds): string
    {
        $centis = (int) round(max(0, $seconds) * 100);

        return sprintf('%d:%02d:%02d.%02d', intdiv($centis, 360000), intdiv($centis, 6000) % 60, intdiv($centis, 100) % 60, $centis % 100);
    }

    /**
     * Strip characters that ASS would read as override tags or line breaks.
     */
    private function clean(string $text): string
    {
        return trim(str_replace(['{', '}', '\\', "\r", "\n"], ['', '', '', ' ', ' '], $text));
    }
}
