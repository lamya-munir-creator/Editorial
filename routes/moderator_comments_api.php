Route::middleware([
    'auth:sanctum',
    'role:admin|editor|moderator',
])
->prefix('moderator')
->group(function () {

    Route::get(
        '/comments',
        [ModeratorCommentController::class, 'index']
    );

    Route::get(
        '/comments/{comment}',
        [ModeratorCommentController::class, 'show']
    );

    Route::patch(
        '/comments/{comment}/approve',
        [ModeratorCommentController::class, 'approve']
    );

    Route::patch(
        '/comments/{comment}/reject',
        [ModeratorCommentController::class, 'reject']
    );

    Route::delete(
        '/comments/{comment}',
        [ModeratorCommentController::class, 'destroy']
    );
});