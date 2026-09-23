<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;
$article = $GLOBALS['cfc_article'] ?? ['title' => 'Blog', 'date' => '', 'html' => ''];
?>
<article class="page-wrap prose">
    <h1><?= cfc_e((string) $article['title']) ?></h1>
    <?php if (!empty($article['date'])): ?>
        <p><time><?= cfc_e((string) $article['date']) ?></time></p>
    <?php endif; ?>
    <div class="post-body"><?= $article['html'] ?></div>
</article>
