<?php
namespace app\modules\online\helpers;

class DialogHelper {
    public function confirm($text, $title = 'Подтверждение', $btn = 'Подтвердить', $dialogSets = [], $key = '1'){
        if(isset($_POST['confirmvals'][$key])){
            return $_POST['confirmvals'][$key] ?: true;
        }

        $arr = [
            'confirm'		=>	$text,
            'conftitle'		=>	$title,
            'confirmbtn'	=>	$btn,
            'confirmdlgsets'=>	$dialogSets,
            'confirmkey'	=>	$key,
        ];

        if(isset($_POST['confirmoverridekeys'])) {
            $arr['confirmoverridekeys'] = $_POST['confirmoverridekeys'];
        }
        return $arr;
    }
}

