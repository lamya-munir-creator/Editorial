<?php

// Fix UpdateArticleRequest
\ = 'C:/xampp/htdocs/Editorial/app/Http/Requests/UpdateArticleRequest.php';
\ = file_get_contents(\);
\ = str_replace(
    "'allow_comments'    => 'sometimes|nullable|boolean',",
    "'allow_comments'    => 'sometimes|nullable|boolean',\n            'author_id'         => 'sometimes|nullable|exists:authors,id',\n            'status'            => 'sometimes|in:draft,published,archived',",
    \
);
file_put_contents(\, \);

// Fix routes
\ = 'C:/xampp/htdocs/Editorial/routes/articles_api.php';
\ = file_get_contents(\);
\ = str_replace(
    "Route::apiResource('tags', TagController::class)->except(['index', 'show']);",
    "Route::apiResource('tags', TagController::class)->except(['index', 'show']);\n        Route::apiResource('articles', ArticleController::class)->except(['index', 'show']);",
    \
);
file_put_contents(\, \);

echo "Restored fixes";
