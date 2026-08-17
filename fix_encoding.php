<?php
\ = 'C:/xampp/htdocs/Editorial/app/Http/Controllers/Api/ArticleController.php';
\ = file_get_contents(\);

// Replace corrupt notifications
\ = preg_replace('/\'[A-Z0-9~\?]+\: \' \. \\\->title/', '\'مقال جديد: \' . \->title', \);
\ = preg_replace('/\'[A-Z0-9~\?]+ \: \' \. \\\->title/', '\'تم تعديل المقال: \' . \->title', \);

// Replace corrupt log messages
\ = preg_replace('/\\\->getActionPrefix\(\) \. \'[A-Z0-9~\? \(\)]+\' \: \\\->getActionPrefix\(\) \. \'[A-Z0-9~\? \(\)]+\'/', '\->getActionPrefix() . \'قام بنشر المقال\' : \->getActionPrefix() . \'قام بإضافة مقال جديد (مسودة)\'', \);
\ = preg_replace('/\\\->getActionPrefix\(\) \. \'[A-Z0-9~\?]+\'/', '\->getActionPrefix() . \'قام بتعديل المقال\'', \);

file_put_contents(\, \);

// Also do AuthorArticleController
\ = 'C:/xampp/htdocs/Editorial/app/Http/Controllers/Api/AuthorArticleController.php';
if (file_exists(\)) {
    \ = file_get_contents(\);
    \ = preg_replace('/\'[A-Z0-9~\?]+\: \' \. \\\->title/', '\'مقال جديد: \' . \->title', \);
    \ = preg_replace('/\'[A-Z0-9~\?]+ \: \' \. \\\->title/', '\'تم تعديل المقال: \' . \->title', \);
    \ = preg_replace('/\\\->getActionPrefix\(\) \. \'[A-Z0-9~\? \(\)]+\' \: \\\->getActionPrefix\(\) \. \'[A-Z0-9~\? \(\)]+\'/', '\->getActionPrefix() . \'قام بنشر المقال\' : \->getActionPrefix() . \'قام بإضافة مقال جديد (مسودة)\'', \);
    \ = preg_replace('/\\\->getActionPrefix\(\) \. \'[A-Z0-9~\?]+\'/', '\->getActionPrefix() . \'قام بتعديل المقال\'', \);
    file_put_contents(\, \);
}

echo "Done";
