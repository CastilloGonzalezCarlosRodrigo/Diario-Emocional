<?php
class EmotionDetector {
    private static $emotions = [
        'Feliz' => ['feliz', 'contento', 'alegre', 'bien', 'optimista', 'gozo', 'dichoso', 'satisfecho'],
        'Triste' => ['triste', 'deprimido', 'melancólico', 'desanimado', 'desesperado', 'abatido', 'desconsolado'],
        'Enojado' => ['enojado', 'furioso', 'irritado', 'molesto', 'indignado', 'airado', 'colérico'],
        'Ansioso' => ['ansioso', 'nervioso', 'preocupado', 'estresado', 'agitado', 'intranquilo', 'atemorizado'],
        'Energético' => ['energía', 'activo', 'motivado', 'vital', 'dinámico', 'vigoroso', 'animado'],
        'Cansado' => ['agotado', 'fatigado', 'exhausto', 'sobrecargado', 'rendido', 'desgastado'],
        'Amoroso' => ['amor', 'cariño', 'ternura', 'afecto', 'pasión', 'adoración', 'compasión'],
        'Gratitud' => ['agradecido', 'gratitud', 'afortunado', 'bendecido', 'reconocido', 'complacido'],
        'Miedo' => ['miedo', 'atemorizado', 'asustado', 'pánico', 'terror', 'aprensión', 'susto'],
        'Orgullo' => ['orgulloso', 'logro', 'triunfo', 'éxito', 'satisfacción', 'dignidad']
    ];
    
    public static function detect($text) {
        $text = strtolower($text);
        $emotionScores = [];
        
        foreach (self::$emotions as $emotion => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $score += 1;
                    // Puntuación extra si la palabra está al inicio
                    if (strpos($text, $keyword) === 0) {
                        $score += 2;
                    }
                }
            }
            if ($score > 0) {
                $emotionScores[$emotion] = $score;
            }
        }
        
        if (empty($emotionScores)) {
            return ['Neutral', 1];
        }
        
        arsort($emotionScores);
        return [key($emotionScores), current($emotionScores)];
    }
    
    public static function getEmotionColor($emotion) {
        $colors = [
            'Feliz' => '#4CAF50',
            'Triste' => '#2196F3',
            'Enojado' => '#F44336',
            'Ansioso' => '#FF9800',
            'Energético' => '#FFEB3B',
            'Cansado' => '#795548',
            'Amoroso' => '#E91E63',
            'Gratitud' => '#8BC34A',
            'Miedo' => '#9C27B0',
            'Orgullo' => '#FF5722',
            'Neutral' => '#9E9E9E'
        ];
        return $colors[$emotion] ?? '#9E9E9E';
    }
    
    public static function getEmotionIcon($emotion) {
        $icons = [
            'Feliz' => '😊',
            'Triste' => '😢',
            'Enojado' => '😠',
            'Ansioso' => '😰',
            'Energético' => '💪',
            'Cansado' => '😴',
            'Amoroso' => '❤️',
            'Gratitud' => '🙏',
            'Miedo' => '😨',
            'Orgullo' => '🦚',
            'Neutral' => '😐'
        ];
        return $icons[$emotion] ?? '😐';
    }
    
    public static function getAllEmotions() {
        return array_keys(self::$emotions);
    }
}
?>