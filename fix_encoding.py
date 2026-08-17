import re

files = [
    r'C:\xampp\htdocs\Editorial\app\Http\Controllers\Api\ArticleController.php',
    r'C:\xampp\htdocs\Editorial\app\Http\Controllers\Api\AuthorArticleController.php'
]

for file in files:
    try:
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        content = re.sub(r"'[A-Z0-9~\? \(\)]+?: ' \. \$article->title", "'مقال جديد: ' . \$article->title", content)
        content = re.sub(r"\$this->getActionPrefix\(\) \. '[A-Z0-9~\? \(\)]+?' : \$this->getActionPrefix\(\) \. '[A-Z0-9~\? \(\)]+?'", "\$this->getActionPrefix() . 'قام بنشر المقال' : \$this->getActionPrefix() . 'قام بإضافة مقال جديد (مسودة)'", content)
        content = re.sub(r"\$this->getActionPrefix\(\) \. 'A~[A-Z0-9~\? \(\)]+?'", "\$this->getActionPrefix() . 'قام بتعديل المقال'", content)
        content = re.sub(r"\$this->getActionPrefix\(\) \. 'AT[A-Z0-9~\? \(\)]+?'", "\$this->getActionPrefix() . 'قام بحذف المقال'", content)
        
        with open(file, 'w', encoding='utf-8') as f:
            f.write(content)
    except FileNotFoundError:
        pass
