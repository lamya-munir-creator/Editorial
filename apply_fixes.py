import os

req_file = r"C:\xampp\htdocs\Editorial\app\Http\Requests\UpdateArticleRequest.php"
with open(req_file, "r", encoding="utf-8") as f:
    req_content = f.read()

req_content = req_content.replace(
    "'allow_comments'    => 'sometimes|nullable|boolean',",
    "'allow_comments'    => 'sometimes|nullable|boolean',\n            'author_id'         => 'sometimes|nullable|exists:authors,id',\n            'status'            => 'sometimes|in:draft,published,archived',"
)
with open(req_file, "w", encoding="utf-8") as f:
    f.write(req_content)

routes_file = r"C:\xampp\htdocs\Editorial\routes\articles_api.php"
with open(routes_file, "r", encoding="utf-8") as f:
    routes_content = f.read()

routes_content = routes_content.replace(
    "Route::apiResource('tags', TagController::class)->except(['index', 'show']);",
    "Route::apiResource('tags', TagController::class)->except(['index', 'show']);\n        Route::apiResource('articles', ArticleController::class)->except(['index', 'show']);"
)
with open(routes_file, "w", encoding="utf-8") as f:
    f.write(routes_content)

print("Fixes applied successfully!")
