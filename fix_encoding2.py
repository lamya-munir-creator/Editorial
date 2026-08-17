import re

files = [
    r'C:\xampp\htdocs\Editorial\app\Http\Controllers\Api\ArticleController.php',
    r'C:\xampp\htdocs\Editorial\app\Http\Controllers\Api\AuthorArticleController.php'
]

for file in files:
    try:
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        content = re.sub(r"Notification::send\(\$admins, new \\App\\Notifications\\SystemAlert\('.*?\) \.", "Notification::send(\$admins, new \\App\\Notifications\\SystemAlert('مقال جديد: ' .", content)
        content = re.sub(r"notify\(new \\App\\Notifications\\SystemAlert\('.*?\) \.", "notify(new \\App\\Notifications\\SystemAlert('مقال جديد: ' .", content)
        content = re.sub(r"'action_label' => \$article->status === 'published' \? \$this->getActionPrefix\(\) \. '.*?' : \$this->getActionPrefix\(\) \. '.*?',", "'action_label' => \$article->status === 'published' ? \$this->getActionPrefix() . 'قام بنشر المقال' : \$this->getActionPrefix() . 'قام بإضافة مقال جديد (مسودة)',", content)
        content = re.sub(r"'action_label' => \$this->getActionPrefix\(\) \. '.*?',", "'action_label' => \$this->getActionPrefix() . 'قام بتعديل المقال',", content)
        
        with open(file, 'w', encoding='utf-8') as f:
            f.write(content)
    except FileNotFoundError:
        pass
