<!-- SEO Optimizer Tool -->
<div class="seo-optimizer-box">
    <div class="seo-box-header">
        <h3>SEO Optimizer</h3>
        <div class="seo-score-pill">
            <div class="score-dot" id="seo-score-circle">0</div>
            <span>SEO Score</span>
        </div>
    </div>

    <div class="seo-box-body">
        <div class="seo-top-row">
            <div class="seo-top-row__keyword">
                <label class="seo-control-label">Focus Keyword</label>
                <div class="seo-inline-field">
                    <input type="text" name="focus_keyword" id="focus_keyword"
                           value="<?= h($post['focus_keyword'] ?? $page['focus_keyword'] ?? '') ?>"
                           placeholder="e.g. digital marketing Mumbai" class="form-control">
                    <button type="button" class="btn btn-outline" onclick="SEOOptimizer.aiGenerate('keyword')">
                        <i class="fas fa-magic"></i> AI
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-primary seo-meta-generate-btn" onclick="SEOOptimizer.aiGenerate('meta')">
                <i class="fas fa-magic"></i> AI Generate All Meta Tags
            </button>
        </div>

        <div class="seo-meta-grid">
            <div class="seo-meta-col-preview">
                <label class="seo-control-label">Search Result Preview</label>
                <div class="google-preview-box">
                    <div class="preview-url" id="preview-url"><?= APP_URL ?>/...</div>
                    <div class="preview-title" id="preview-title">Loading...</div>
                    <div class="preview-desc" id="preview-desc">Enter a meta description...</div>
                </div>
            </div>

            <div class="seo-meta-col-fields">
                <div class="form-group">
                    <label class="seo-control-label">SEO Title <span class="char-count" id="title-counter">0/60</span></label>
                    <div class="seo-inline-field">
                        <input type="text" name="meta_title" id="meta_title"
                               value="<?= h($post['meta_title'] ?? $page['meta_title'] ?? '') ?>"
                               class="form-control" placeholder="Meta Title...">
                        <button type="button" class="btn btn-outline" onclick="SEOOptimizer.aiGenerate('title')">
                            <i class="fas fa-magic"></i> AI Title
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="seo-control-label">Meta Description <span class="char-count" id="desc-counter">0/160</span></label>
                    <div class="seo-inline-field seo-inline-field--stack">
                        <textarea name="meta_description" id="meta_description" rows="4"
                                  class="form-control" placeholder="Meta Description..."><?= h($post['meta_description'] ?? $page['meta_description'] ?? '') ?></textarea>
                        <button type="button" class="btn btn-outline" onclick="SEOOptimizer.aiGenerate('description')">
                            <i class="fas fa-magic"></i> AI Desc
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="checklist-section">
            <div class="checklist-title"><i class="fas fa-list-ul"></i> Optimization Checklist</div>
            <div class="seo-checklist-grid" id="seo-checklist"></div>
        </div>
    </div>
</div>

<script>
    const SEOOptimizer = {
        META_TITLE_MAX: 60,
        META_DESC_MAX: 160,

        init() {
            this.bindEvents();
            this.update();
        },

        bindEvents() {
            ['title', 'slug', 'focus_keyword', 'meta_title', 'meta_description'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', () => this.update());
            });
            if (typeof quill !== 'undefined') {
                quill.on('text-change', () => this.update());
            } else {
                ['content', 'raw-html-editor'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('input', () => this.update());
                });
            }
        },

        async aiGenerate(type) {
            const title = document.getElementById('title')?.value || '';
            const focus = document.getElementById('focus_keyword')?.value || '';
            let content = '';
            if (typeof quill !== 'undefined') content = quill.root.innerText;
            else content = (document.getElementById('content') || document.getElementById('raw-html-editor'))?.value.replace(/<[^>]*>?/gm, '') || '';

            let prompt = "";
            if (type === 'keyword') prompt = `Suggest a 2-4 word SEO focus keyword for: "${title}". Content: ${content.substring(0, 300)}. Return ONLY keyword.`;
            else if (type === 'title') prompt = `Suggest an SEO meta title (max ${this.META_TITLE_MAX} chars) for: "${title}". Must include keyword "${focus}". Return ONLY the title.`;
            else if (type === 'description') prompt = `Write an SEO meta description (120-${this.META_DESC_MAX} chars) for "${title}". MUST include focus keyword "${focus}". Return ONLY the description.`;
            else if (type === 'meta') prompt = `Generate SEO Title (max ${this.META_TITLE_MAX} chars) and Meta Description (120-${this.META_DESC_MAX} chars) for "${title}". BOTH must include focus keyword "${focus}". Format: Title | Description. Return ONLY this.`;

            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const body = new URLSearchParams();
                body.append('messages', JSON.stringify([{ role: 'user', content: prompt }]));
                const res = await fetch("https://ubsa.in/smartprogrammers/test/z.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: body.toString()
                });
                const data = await res.json();
                if (data && data.result) {
                    const result = data.result.trim().replace(/^"|"$/g, '');
                    if (type === 'keyword') document.getElementById('focus_keyword').value = result;
                    else if (type === 'title') document.getElementById('meta_title').value = result;
                    else if (type === 'description') document.getElementById('meta_description').value = result;
                    else if (type === 'meta') {
                        const parts = result.split('|');
                        if (parts[0]) document.getElementById('meta_title').value = parts[0].trim();
                        if (parts[1]) document.getElementById('meta_description').value = parts[1].trim();
                    }
                    this.update();
                }
            } catch (e) { console.error('AI Error:', e); }
            finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        },

        update() {
            const title = document.getElementById('title')?.value || '';
            const slug = document.getElementById('slug')?.value || '';
            const focus = document.getElementById('focus_keyword')?.value.trim().toLowerCase() || '';
            const metaTitle = document.getElementById('meta_title')?.value || title;
            const metaDesc = document.getElementById('meta_description')?.value || '';

            let content = '';
            if (typeof quill !== 'undefined') content = quill.root.innerText;
            else content = (document.getElementById('content') || document.getElementById('raw-html-editor'))?.value.replace(/<[^>]*>?/gm, '') || '';

            const META_TITLE_MAX = this.META_TITLE_MAX;
            const META_DESC_MAX = this.META_DESC_MAX;

            let score = 0;
            const checks = [];

            const tLen = metaTitle.length;
            const titleCounter = document.getElementById('title-counter');
            if (titleCounter) {
                titleCounter.textContent = `${tLen}/${META_TITLE_MAX}`;
                titleCounter.classList.toggle('char-count--warn', tLen > META_TITLE_MAX);
            }
            if (tLen >= 30 && tLen <= META_TITLE_MAX) {
                score += 20;
                checks.push({ l: 'Title length is good', s: 'pass' });
            } else {
                checks.push({
                    l: 'Title length: ' + (tLen === 0 ? 'Add a title' : (tLen < 30 ? 'Too short' : 'Too long')),
                    s: tLen > 0 ? 'fail' : 'pending'
                });
            }

            const titleLow = metaTitle.toLowerCase();
            if (focus && titleLow.includes(focus)) { score += 20; checks.push({ l: 'Keyword in Title', s: 'pass' }); }
            else { checks.push({ l: 'No keyword in Title', s: focus ? 'fail' : 'pending' }); }

            const dLen = metaDesc.length;
            const descCounter = document.getElementById('desc-counter');
            if (descCounter) {
                descCounter.textContent = `${dLen}/${META_DESC_MAX}`;
                descCounter.classList.toggle('char-count--warn', dLen > META_DESC_MAX);
            }
            if (dLen >= 70 && dLen <= META_DESC_MAX) {
                score += 20;
                checks.push({ l: 'Description length is good', s: 'pass' });
            } else {
                checks.push({
                    l: 'Description length: ' + (dLen === 0 ? 'Add a description' : (dLen < 70 ? 'Too short' : 'Too long')),
                    s: dLen > 0 ? 'fail' : 'pending'
                });
            }

            const descLow = metaDesc.toLowerCase();
            const focusWords = focus.split(/\s+/).filter(w => w.length > 2);
            const hasKeyword = focus && descLow.includes(focus);
            const containsWords = focusWords.length > 0 && focusWords.every(w => descLow.includes(w));

            if (hasKeyword || containsWords) { score += 15; checks.push({ l: 'Keyword in Meta', s: 'pass' }); }
            else { checks.push({ l: 'No keyword in Meta', s: focus ? 'fail' : 'pending' }); }

            const wordCount = content.trim() ? content.trim().split(/\s+/).length : 0;
            if (wordCount >= 300) { score += 15; checks.push({ l: `Content: ${wordCount} words`, s: 'pass' }); }
            else { checks.push({ l: `Content count: ${wordCount}`, s: wordCount > 0 ? 'fail' : 'pending' }); }

            if (focus && slug.toLowerCase().includes(focus.replace(/\s+/g, '-').substring(0, 15))) { score += 10; checks.push({ l: 'Keyword in URL', s: 'pass' }); }
            else { checks.push({ l: 'No keyword in URL', s: focus ? 'fail' : 'pending' }); }

            const badge = document.getElementById('seo-score-circle');
            if (badge) {
                badge.textContent = score;
                badge.style.background = score >= 80 ? '#22c55e' : (score >= 50 ? '#f59e0b' : '#ef4444');
            }

            const pTitle = document.getElementById('preview-title');
            if (pTitle) pTitle.textContent = (metaTitle || 'Untitled') + ' | <?= h(APP_NAME) ?>';

            const pDesc = document.getElementById('preview-desc');
            if (pDesc) pDesc.textContent = metaDesc || 'Provide a meta description...';

            const pUrl = document.getElementById('preview-url');
            if (pUrl) pUrl.textContent = '<?= APP_URL ?>/' + (slug || '...');

            const checklist = document.getElementById('seo-checklist');
            if (checklist) {
                checklist.innerHTML = '';
                checks.forEach(c => {
                    const div = document.createElement('div');
                    div.className = `checklist-item item-${c.s}`;
                    div.innerHTML = `<i class="fas ${c.s === 'pass' ? 'fa-check-circle' : (c.s === 'fail' ? 'fa-times-circle' : 'fa-circle')}"></i> ${c.l}`;
                    checklist.appendChild(div);
                });
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => SEOOptimizer.init());
</script>
