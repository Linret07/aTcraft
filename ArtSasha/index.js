let currentCommentsDrawingId = null;

window.openLoginModal = function() {
    var loginModal = document.getElementById('loginModal');
    if (loginModal) {
        loginModal.style.display = 'flex';
    }
};

window.closeLoginModal = function() {
    var loginModal = document.getElementById('loginModal');
    if (loginModal) {
        loginModal.style.display = 'none';
    }
};

var closeLoginBtn = document.getElementById('closeModalBtn');
if (closeLoginBtn) {
    closeLoginBtn.onclick = window.closeLoginModal;
}

// СЛАЙДЕР
var currentImageIndex = 0;
var allGalleryImages = [];

function viewFullImage(clickedSrc, clickedTitle) {
    allGalleryImages = [];
    var imgElements = document.querySelectorAll('.card img');
    imgElements.forEach(function(img, index) {
        allGalleryImages.push({
            src: img.getAttribute('src'),
            title: img.getAttribute('alt').replace('Малюнок Олександри — ', '')
        });
        if (img.getAttribute('src') === clickedSrc) {
            currentImageIndex = index;
        }
    });
    document.getElementById('imageModal').style.display = 'flex';
    updateModalImage();
}

function closeFullImage() {
    document.getElementById('imageModal').style.display = 'none';
}

function updateModalImage() {
    var currentData = allGalleryImages[currentImageIndex];
    if (currentData) {
        document.getElementById('modalImage').src = currentData.src;
        document.getElementById('modalImageTitle').textContent = currentData.title;
    }
}

function changeImage(direction) {
    if (allGalleryImages.length <= 1) return;
    currentImageIndex += direction;
    if (currentImageIndex >= allGalleryImages.length) {
        currentImageIndex = 0;
    } else if (currentImageIndex < 0) {
        currentImageIndex = allGalleryImages.length - 1;
    }
    updateModalImage();
}

document.addEventListener('keydown', function(e) {
    var imgModal = document.getElementById('imageModal');
    if (imgModal && imgModal.style.display === 'flex') {
        if (e.key === 'ArrowRight') changeImage(1);
        if (e.key === 'ArrowLeft') changeImage(-1);
        if (e.key === 'Escape') closeFullImage();
    }
});

window.onclick = function(event) {
    var imgModal = document.getElementById('imageModal');
    var loginModal = document.getElementById('loginModal');
    var commentsModal = document.getElementById('commentsModal');
    if (event.target === imgModal) closeFullImage();
    if (event.target === loginModal) loginModal.style.display = 'none';
    if (event.target === commentsModal) commentsModal.style.display = 'none';
};

// КОМЕНТАРІ
window.openComments = function(drawingId, drawingTitle) {
    var commentsModal = document.getElementById('commentsModal');
    var commentsTitle = document.getElementById('commentsTitle');
    var drawingIdInput = document.getElementById('commentsDrawingId');
    var commentsError = document.getElementById('commentsError');
    if (!commentsModal || !commentsTitle || !drawingIdInput) return;
    currentCommentsDrawingId = drawingId;
    drawingIdInput.value = drawingId;
    commentsTitle.textContent = drawingTitle;
    if (commentsError) {
        commentsError.textContent = '';
        commentsError.style.display = 'none';
    }
    loadComments(drawingId, true);
};

function loadComments(drawingId, showModal) {
    var commentsList = document.getElementById('commentsList');
    var commentsModal = document.getElementById('commentsModal');
    var commentsError = document.getElementById('commentsError');
    if (!commentsList) return;
    commentsList.innerHTML = '<p style="color:#64748b;">Завантаження коментарів...</p>';
    fetch('comments_ajax.php?action=get&drawing_id=' + encodeURIComponent(drawingId))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!Array.isArray(data)) throw new Error('Невірний формат відповіді');
            if (data.length === 0) {
                commentsList.innerHTML = '<p style="color:#64748b;margin:0;">Поки нема коментарів. Станьте першим!</p>';
            } else {
                commentsList.innerHTML = data.map(function(comment) {
                    return '<div class="comment-item"><div class="comment-meta"><span class="comment-name">' +
                        escapeHtml(comment.name) + '</span><span>' + escapeHtml(comment.date) + '</span></div>' +
                        '<div class="comment-text">' + escapeHtml(comment.text) + '</div>' +
                        (isAdmin ? '<button class="comment-delete-btn" aria-label="Видалити коментар" onclick="deleteComment(' + comment.id + ',' + drawingId + ')">✖</button>' : '') +
                        '</div>';
                }).join('');
            }
            updateCommentCountBadge(drawingId, data.length);
            if (showModal && commentsModal) commentsModal.style.display = 'flex';
        })
        .catch(function(error) {
            if (commentsError) {
                commentsError.textContent = 'Помилка завантаження коментарів.';
                commentsError.style.display = 'block';
            }
            commentsList.innerHTML = '<p style="color:#e53e3e;margin:0;">Не вдалося завантажити коментарі.</p>';
            console.error(error);
        });
}

function deleteComment(commentId, drawingId) {
    if (!confirm('Ви впевнені, що хочете видалити цей коментар?')) return;
    var formData = new FormData();
    formData.append('comment_id', commentId);
    fetch('comments_ajax.php?action=delete', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                loadComments(drawingId, true);
            } else {
                alert(result.error || 'Не вдалося видалити коментар.');
            }
        })
        .catch(function(error) {
            console.error('Delete comment error:', error);
            alert('Сталася помилка при видаленні коментаря.');
        });
}

function submitComment(event) {
    event.preventDefault();
    var nameField = document.getElementById('commentName');
    var textField = document.getElementById('commentText');
    var drawingIdInput = document.getElementById('commentsDrawingId');
    var commentsError = document.getElementById('commentsError');
    if (!nameField || !textField || !drawingIdInput) return;
    var name = nameField.value.trim();
    var text = textField.value.trim();
    var drawingId = drawingIdInput.value;
    if (!name || !text) {
        if (commentsError) {
            commentsError.textContent = 'Будь ласка, заповніть усі поля.';
            commentsError.style.display = 'block';
        }
        return;
    }
    var formData = new FormData();
    formData.append('drawing_id', drawingId);
    formData.append('name', name);
    formData.append('text', text);
    fetch('comments_ajax.php?action=add', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                nameField.value = '';
                textField.value = '';
                if (commentsError) { commentsError.textContent = ''; commentsError.style.display = 'none'; }
                loadComments(drawingId, true);
            } else {
                if (commentsError) {
                    commentsError.textContent = result.error || 'Не вдалося додати коментар.';
                    commentsError.style.display = 'block';
                }
            }
        })
        .catch(function(error) {
            if (commentsError) { commentsError.textContent = 'Помилка при збереженні коментаря.'; commentsError.style.display = 'block'; }
            console.error(error);
        });
}

function updateCommentCountBadge(drawingId, count) {
    document.querySelectorAll('.btn-comment-count[data-btn-id="' + drawingId + '"]').forEach(function(badge) {
        badge.textContent = count;
    });
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

var commentsForm = document.getElementById('commentsForm');
if (commentsForm) commentsForm.addEventListener('submit', submitComment);

var closeCommentsBtn = document.getElementById('closeCommentsBtn');
if (closeCommentsBtn) {
    closeCommentsBtn.addEventListener('click', function() {
        var commentsModal = document.getElementById('commentsModal');
        if (commentsModal) commentsModal.style.display = 'none';
    });
}

document.addEventListener('keydown', function(e) {
    var commentsModal = document.getElementById('commentsModal');
    if (commentsModal && commentsModal.style.display === 'flex' && e.key === 'Escape') {
        commentsModal.style.display = 'none';
    }
});

// ЛАЙКИ
function getLikedDrawings() {
    try { return JSON.parse(localStorage.getItem('likedDrawings') || '[]'); } catch (e) { return []; }
}

function setLikedDrawing(drawingId, shouldBeLiked) {
    var liked = getLikedDrawings();
    var index = liked.indexOf(drawingId);
    if (shouldBeLiked && index === -1) {
        liked.push(drawingId);
    } else if (!shouldBeLiked && index !== -1) {
        liked.splice(index, 1);
    }
    try {
        localStorage.setItem('likedDrawings', JSON.stringify(liked));
    } catch (e) {
        // Some mobile browsers/private modes may block storage writes.
    }
}

function isDrawingLiked(drawingId) {
    return getLikedDrawings().indexOf(drawingId) !== -1;
}

function initLikedButtons() {
    document.querySelectorAll('.like-btn').forEach(function(button) {
        var drawingId = parseInt(button.getAttribute('data-id'), 10);
        if (!drawingId || isNaN(drawingId)) return;
        if (isDrawingLiked(drawingId)) {
            button.classList.add('liked');
        }
    });
}

function initLivePaintingsHover() {
    var fishPattern = /(риб|рыб|fish|fishes|goldfish|koi|акваріум|аквариум)/i;
    var ghostPattern = /(привид|привиди|призрак|привидение|примар|дух|ghost|ghoul|spirit|phantom|specter|spectre)/i;

    document.querySelectorAll('.gallery .card').forEach(function(card) {
        var titleEl = card.querySelector('h3');
        var titleText = titleEl ? titleEl.textContent : '';
        var imageEl = card.querySelector('img');
        var imageSrc = imageEl ? (imageEl.getAttribute('src') || '') : '';
        var tagsText = Array.from(card.querySelectorAll('.tag-item-btn'))
            .map(function(tag) { return tag.textContent; })
            .join(' ');
        var lookupText = (titleText + ' ' + tagsText + ' ' + imageSrc).toLowerCase();

        if (fishPattern.test(lookupText)) {
            card.classList.add('hover-fish');
        }
        if (ghostPattern.test(lookupText)) {
            card.classList.add('hover-ghost');
        }
    });
}

function sendLike(button, drawingId) {
    if (!button) return;
    button.disabled = true;
    var likeCount = button.querySelector('.like-count');
    var likedNow = button.classList.contains('liked') || isDrawingLiked(drawingId);
    var action = likedNow ? 'unlike' : 'like';

    fetch('like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(drawingId) + '&action=' + encodeURIComponent(action)
    })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data && data.success) {
                if (likeCount) likeCount.textContent = data.likes;
                if (action === 'like') {
                    button.classList.add('liked');
                    setLikedDrawing(drawingId, true);
                } else {
                    button.classList.remove('liked');
                    setLikedDrawing(drawingId, false);
                }
                button.disabled = false;
            } else {
                button.disabled = false;
            }
        })
        .catch(function(error) {
            console.error('Like error:', error);
            button.disabled = false;
        });
}

// ТЕМА
function setThemeButtonText() {
    var btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    btn.textContent = document.body.classList.contains('dark-theme') ? '☀️ Денна тема' : '🌌 Космічна тема';

    var halloweenBtn = document.getElementById('halloweenToggleBtn');
    if (halloweenBtn) {
        halloweenBtn.textContent = document.body.classList.contains('halloween-theme') ? '✨ Вимкнути Хелловін' : '🎃 Хелловін';
    }
}

function updateThemeMenuButton() {
    var menuButton = document.getElementById('themeMenuToggle');
    var picker = document.querySelector('.theme-picker');
    if (!menuButton) return;
    var theme = document.body.classList.contains('halloween-theme') ? 'halloween' : (document.body.classList.contains('dark-theme') ? 'dark' : 'light');
    var labels = { light: '☀️ Денна тема', dark: '🌌 Космічна тема', halloween: '🎃 Хелловін' };
    menuButton.textContent = labels[theme] + ' ▾';
    document.querySelectorAll('.theme-menu [data-theme]').forEach(function(option) {
        option.classList.toggle('active', option.dataset.theme === theme);
    });
    if (picker) picker.classList.remove('open');
    menuButton.setAttribute('aria-expanded', 'false');
}

function toggleThemeMenu() {
    var picker = document.querySelector('.theme-picker');
    var menuButton = document.getElementById('themeMenuToggle');
    if (!picker || !menuButton) return;
    var isOpen = picker.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(isOpen));
}

function selectTheme(theme) {
    document.body.classList.remove('dark-theme', 'halloween-theme');
    if (theme === 'dark') document.body.classList.add('dark-theme');
    if (theme === 'halloween') document.body.classList.add('halloween-theme');
    localStorage.setItem('artsashaTheme', theme);
    updateThemeMenuButton();
}

function toggleTheme() {
    document.body.classList.remove('halloween-theme');
    document.body.classList.toggle('dark-theme');
    var isDark = document.body.classList.contains('dark-theme');
    localStorage.setItem('artsashaTheme', isDark ? 'dark' : 'light');
    setThemeButtonText();
}

function toggleHalloweenTheme() {
    var isHalloween = document.body.classList.toggle('halloween-theme');
    document.body.classList.remove('dark-theme');
    localStorage.setItem('artsashaTheme', isHalloween ? 'halloween' : 'light');
    setThemeButtonText();
}

function initTheme() {
    var savedTheme = localStorage.getItem('artsashaTheme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    } else if (savedTheme === 'halloween') {
        document.body.classList.add('halloween-theme');
    }
    setThemeButtonText();
    updateThemeMenuButton();
}

document.addEventListener('DOMContentLoaded', function() {
    initLikedButtons();
    initTheme();
    initLivePaintingsHover();

    document.addEventListener('click', function(event) {
        var picker = document.querySelector('.theme-picker');
        if (picker && !picker.contains(event.target)) {
            picker.classList.remove('open');
            var menuButton = document.getElementById('themeMenuToggle');
            if (menuButton) menuButton.setAttribute('aria-expanded', 'false');
        }
    });

    var scrollBtn = document.getElementById('scrollToTopBtn');
    if (scrollBtn) {
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    window.addEventListener('scroll', function() {
        if (!scrollBtn) return;
        if (window.pageYOffset > 220) {
            scrollBtn.style.opacity = '1';
            scrollBtn.style.visibility = 'visible';
        } else {
            scrollBtn.style.opacity = '0';
            scrollBtn.style.visibility = 'hidden';
        }
    });
});
