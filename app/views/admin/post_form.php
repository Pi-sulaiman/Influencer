<?php /** @var array|null $post @var array $data @var array $errors @var array $categories */
$pubValue = $data['published_at'] ? date('Y-m-d\TH:i', strtotime($data['published_at'])) : '';
$siteHost = preg_replace('#^https?://#', '', rtrim(abs_url(''), '/'));
?>
<form method="post" enctype="multipart/form-data" action="<?= e(url($post ? 'admin/posts/' . $post['id'] . '/edit' : 'admin/posts/new')) ?>" data-post-form>
    <?= csrf_field() ?>
    <div class="page-head">
        <div class="flex">
            <a class="icon-btn" href="<?= e(url('admin/posts')) ?>" aria-label="Back to posts"><i class="ph-bold ph-arrow-left"></i></a>
            <div><h2><?= $post ? 'Edit post' : 'New post' ?></h2><p><?= $post ? 'Last saved ' . e(time_ago($post['updated_at'])) : 'Write it, check the SEO panel, then publish.' ?></p></div>
        </div>
        <div class="actions">
            <?php if ($post): ?><a class="btn btn-outline" href="<?= e(url('blog/' . $post['slug'])) ?>" target="_blank"><i class="ph ph-eye"></i><?= $post['status'] === 'published' ? 'View' : 'Preview' ?></a><?php endif; ?>
            <button class="btn btn-outline" type="submit" name="status" value="draft"><i class="ph ph-floppy-disk"></i>Save draft</button>
            <button class="btn btn-primary" type="submit" name="status" value="published"><i class="ph-bold ph-paper-plane-tilt"></i><?= $post && $post['status'] === 'published' ? 'Update' : 'Publish' ?></button>
        </div>
    </div>

    <?php foreach ($errors as $er): ?><div class="alert alert-error"><i class="ph-fill ph-warning-circle"></i><?= e($er) ?></div><?php endforeach; ?>

    <div class="grid grid-side">
        <div class="grid">
            <section class="card card-pad">
                <label class="sr-only" for="ptitle">Title</label>
                <input class="input title-input" id="ptitle" name="title" value="<?= e($data['title']) ?>" placeholder="Post title" required data-seo-title>
                <div class="slug-row">
                    <i class="ph ph-link"></i><span><?= e($siteHost) ?>/blog/</span>
                    <label class="sr-only" for="pslug">URL slug</label>
                    <input id="pslug" name="slug" value="<?= e($data['slug']) ?>" placeholder="auto-from-title" data-seo-slug<?= $post ? '' : ' data-auto="1"' ?>>
                </div>
            </section>

            <section class="editor" data-editor>
                <div class="ed-toolbar" role="toolbar" aria-label="Formatting">
                    <button type="button" data-cmd="formatBlock" data-val="P" title="Paragraph">P</button>
                    <button type="button" data-cmd="formatBlock" data-val="H2" title="Heading 2">H2</button>
                    <button type="button" data-cmd="formatBlock" data-val="H3" title="Heading 3">H3</button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="bold" title="Bold (Ctrl+B)"><i class="ph-bold ph-text-b"></i></button>
                    <button type="button" data-cmd="italic" title="Italic (Ctrl+I)"><i class="ph-bold ph-text-italic"></i></button>
                    <button type="button" data-cmd="underline" title="Underline"><i class="ph-bold ph-text-underline"></i></button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="insertUnorderedList" title="Bullet list"><i class="ph-bold ph-list-bullets"></i></button>
                    <button type="button" data-cmd="insertOrderedList" title="Numbered list"><i class="ph-bold ph-list-numbers"></i></button>
                    <button type="button" data-cmd="formatBlock" data-val="BLOCKQUOTE" title="Quote"><i class="ph-bold ph-quotes"></i></button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="link" title="Add link"><i class="ph-bold ph-link"></i></button>
                    <button type="button" data-cmd="unlink" title="Remove link"><i class="ph-bold ph-link-break"></i></button>
                    <button type="button" data-cmd="image" title="Insert image"><i class="ph-bold ph-image"></i></button>
                    <button type="button" data-cmd="youtube" title="Embed YouTube video"><i class="ph-bold ph-youtube-logo"></i></button>
                    <button type="button" data-cmd="insertHorizontalRule" title="Divider"><i class="ph-bold ph-minus"></i></button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="removeFormat" title="Clear formatting"><i class="ph-bold ph-eraser"></i></button>
                    <button type="button" data-cmd="source" title="Edit HTML"><i class="ph-bold ph-code"></i></button>
                </div>
                <div class="ed-area" contenteditable="true" data-ed-area data-placeholder="Start writing. Use H2 headings for each main section; they become the table of contents."><?= $data['content'] ?></div>
                <textarea class="ed-source" data-ed-source hidden aria-label="HTML source"></textarea>
                <textarea name="content" data-ed-input hidden><?= e($data['content']) ?></textarea>
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-ed-file hidden>
                <div class="ed-foot"><span data-ed-words>0 words</span><span data-ed-read>1 min read</span></div>
            </section>

            <section class="card card-pad">
                <div class="field">
                    <label for="pexcerpt">Excerpt</label>
                    <textarea class="textarea" id="pexcerpt" name="excerpt" rows="3" maxlength="600" placeholder="One or two sentences shown on the blog list and under the title"><?= e($data['excerpt']) ?></textarea>
                </div>
            </section>

            <section class="card card-pad" data-seo-panel>
                <div class="flex-between" style="margin-bottom:16px">
                    <h2 style="font-size:16px">Search engine preview</h2>
                    <div class="seo-score"><div class="ring" data-seo-ring><span data-seo-score>0</span></div><div><b data-seo-label>Needs work</b><div class="small muted">SEO score</div></div></div>
                </div>
                <div class="serp" aria-hidden="true">
                    <div class="s-site"><span class="s-fav"><?= mark_svg('') ?></span><div><div><?= e(setting('site_name', 'Peakilo')) ?></div><div class="s-url"><?= e($siteHost) ?> &rsaquo; blog &rsaquo; <span data-serp-slug>post</span></div></div></div>
                    <div class="s-title" data-serp-title>Post title</div>
                    <div class="s-desc" data-serp-desc>Add a meta description to control what Google shows here.</div>
                </div>
                <div class="form-grid mt-16">
                    <div class="field full">
                        <label for="pkw">Focus keyword</label>
                        <input class="input" id="pkw" name="focus_keyword" value="<?= e($data['focus_keyword']) ?>" placeholder="e.g. influencer marketing agency" data-seo-kw>
                        <span class="hint">The main phrase you want this post to rank for.</span>
                    </div>
                    <div class="field full">
                        <label for="pmt">SEO title <span class="muted">(optional, defaults to the post title)</span></label>
                        <input class="input" id="pmt" name="meta_title" value="<?= e($data['meta_title']) ?>" maxlength="255" data-seo-mt>
                        <div class="counter-row"><span data-mt-count>0 characters</span><span>Aim for 30 to 60</span></div>
                        <div class="meter"><span data-mt-meter></span></div>
                    </div>
                    <div class="field full">
                        <label for="pmd">Meta description</label>
                        <textarea class="textarea" id="pmd" name="meta_description" rows="3" maxlength="320" data-seo-md placeholder="A clear summary that makes people want to click"><?= e($data['meta_description']) ?></textarea>
                        <div class="counter-row"><span data-md-count>0 characters</span><span>Aim for 120 to 160</span></div>
                        <div class="meter"><span data-md-meter></span></div>
                    </div>
                </div>
                <h3 class="small muted mt-24" style="text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Checklist</h3>
                <ul class="seo-checks" data-seo-checks></ul>
            </section>
        </div>

        <aside class="grid" style="position:sticky;top:88px">
            <section class="card card-pad">
                <h2 style="font-size:16px;margin-bottom:16px">Publishing</h2>
                <div class="field">
                    <span class="label">Status</span>
                    <div><span class="badge b-<?= e($data['status']) ?>"><?= e(status_label($data['status'])) ?></span></div>
                </div>
                <div class="field mt-16">
                    <label for="ppub">Publish date</label>
                    <input class="input" id="ppub" type="datetime-local" name="published_at" value="<?= e($pubValue) ?>">
                    <span class="hint">Set a future date to schedule the post.</span>
                </div>
                <div class="field mt-16">
                    <label for="pcat">Category</label>
                    <select class="select" id="pcat" name="category_id">
                        <option value="">Uncategorised</option>
                        <?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id'] ?>"<?= (int) $data['category_id'] === (int) $cat['id'] ? ' selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?>
                    </select>
                    <a class="hint" href="<?= e(url('admin/categories')) ?>" style="text-decoration:underline">Manage categories</a>
                </div>
                <div class="field mt-16">
                    <label for="pauthor">Author name</label>
                    <input class="input" id="pauthor" name="author_name" value="<?= e($data['author_name']) ?>">
                </div>
                <label class="check mt-16"><input type="checkbox" name="featured" value="1"<?= $data['featured'] ? ' checked' : '' ?>>Feature at the top of the blog</label>
            </section>
            <section class="card card-pad">
                <h2 style="font-size:16px;margin-bottom:12px">Cover image</h2>
                <label class="cover-drop" data-cover-drop>
                    <?php if (!empty($data['cover_image'])): ?><img src="<?= e(upload_url($data['cover_image'])) ?>" alt=""><?php endif; ?>
                    <span><i class="ph ph-image"></i>Upload a 1600 x 900 image</span>
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-cover-input>
                </label>
                <div class="field mt-16">
                    <label for="palt">Image alt text</label>
                    <input class="input" id="palt" name="cover_alt" value="<?= e($data['cover_alt']) ?>" placeholder="Describe the image for SEO and screen readers" data-seo-alt>
                </div>
                <?php if (!empty($data['cover_image'])): ?><label class="check mt-8"><input type="checkbox" name="remove_cover" value="1">Remove cover image</label><?php endif; ?>
            </section>
            <?php if ($post): ?>
                <section class="card card-pad">
                    <button class="btn btn-danger btn-block" type="submit" formaction="<?= e(url('admin/posts/' . $post['id'] . '/delete')) ?>" formnovalidate data-confirm="Delete this post permanently?"><i class="ph ph-trash"></i>Delete post</button>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</form>
