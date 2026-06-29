/*
 * File: assets/js/main.js
 * Chức năng: Xử lý JavaScript dùng chung cho website.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (window.AOS) {
        AOS.init({
            duration: 700,
            easing: 'ease-out-cubic',
            once: true,
            offset: 80
        });
    }

    var navbar = document.querySelector('.navbar');
    if (navbar) {
        var updateNavbar = function () {
            var scrolled = window.scrollY > 8;
            navbar.classList.toggle('shadow', scrolled);
            navbar.classList.toggle('navbar-scrolled', scrolled);
        };

        updateNavbar();
        window.addEventListener('scroll', updateNavbar);
    }

    var chatToggle = document.getElementById('faqChatToggle');
    var chatPanel = document.getElementById('faqChatPanel');
    var chatClose = document.getElementById('faqChatClose');
    var chatForm = document.getElementById('faqChatForm');
    var chatMessages = document.getElementById('faqChatMessages');
    var chatInput = document.getElementById('faqChatInput');

    if (chatToggle && chatPanel && chatClose && chatForm && chatMessages && chatInput) {
        chatToggle.addEventListener('click', function () {
            chatPanel.hidden = false;
            chatInput.focus();
        });

        chatClose.addEventListener('click', function () {
            chatPanel.hidden = true;
        });

        chatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var text = chatInput.value.trim();
            if (!text) {
                return;
            }

            appendChatMessage(text, 'user-message');
            chatInput.value = '';
            chatInput.disabled = true;
            appendChatMessage('Đang gửi...', 'bot-message', true);

            fetch('http://127.0.0.1:1884/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ question: text })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    removeTemporaryMessage();
                    var reply = getChatReplyText(data);
                    appendChatMessage(reply || 'Xin lỗi, hiện không trả lời được. Vui lòng thử lại sau.', 'bot-message');
                })
                .catch(function () {
                    removeTemporaryMessage();
                    appendChatMessage('Lỗi kết nối tới chatbot. Vui lòng kiểm tra server RAG.', 'bot-message');
                })
                .finally(function () {
                    chatInput.disabled = false;
                    chatInput.focus();
                });
        });
    }

    initHlsVideos();
    initFaqInteractions();
});

function initHlsVideos() {
    var videos = Array.prototype.slice.call(document.querySelectorAll('video[data-hls-src]'));

    videos.forEach(function (video) {
        var source = video.dataset.hlsSrc;
        if (!source) {
            return;
        }

        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = source;
            video.play().catch(function () {});
            return;
        }

        if (window.Hls && window.Hls.isSupported()) {
            var hls = new window.Hls();
            hls.loadSource(source);
            hls.attachMedia(video);
            hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
                video.play().catch(function () {});
            });
        }
    });
}

function appendChatMessage(text, className, temporary) {
    var message = document.createElement('div');
    message.className = 'faq-chat-message ' + className;
    message.innerHTML = formatText(text);
    if (temporary) {
        message.dataset.temp = 'true';
        message.style.opacity = '0.7';
    }
    var chatMessages = document.getElementById('faqChatMessages');
    chatMessages.appendChild(message);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function getChatReplyText(data) {
    if (!data || !data.response) {
        return '';
    }

    if (typeof data.response === 'string') {
        return data.response;
    }

    if (Array.isArray(data.response) && data.response.length > 0) {
        if (typeof data.response[0] === 'string') {
            return data.response.join('');
        }

        if (data.response[0].type === 'text' && data.response[0].text) {
            return data.response[0].text;
        }
    }

    return '';
}

function formatText(text) {
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/^\* /gm, '- ');
    return text;
}

function removeTemporaryMessage() {
    var chatMessages = document.getElementById('faqChatMessages');
    if (!chatMessages) {
        return;
    }
    var temp = chatMessages.querySelector('[data-temp="true"]');
    if (temp) {
        chatMessages.removeChild(temp);
    }
}

function initFaqInteractions() {
    var searchInput = document.getElementById('faqSearchInput');
    var filters = Array.prototype.slice.call(document.querySelectorAll('.faq-filter'));
    var items = Array.prototype.slice.call(document.querySelectorAll('.faq-item'));
    var emptyState = document.getElementById('faqEmptyState');
    var promptChips = Array.prototype.slice.call(document.querySelectorAll('.faq-prompt-chip'));

    if (items.length > 0) {
        var activeFilter = 'all';

        var applyFaqFilter = function () {
            var keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var visibleCount = 0;

            items.forEach(function (item) {
                var category = item.dataset.category || 'all';
                var content = (item.dataset.search || item.textContent || '').toLowerCase();
                var matchesFilter = activeFilter === 'all' || category === activeFilter;
                var matchesSearch = !keyword || content.indexOf(keyword) !== -1;
                var isVisible = matchesFilter && matchesSearch;

                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount++;
                }
            });

            if (emptyState) {
                emptyState.hidden = visibleCount > 0;
            }
        };

        filters.forEach(function (button) {
            button.addEventListener('click', function () {
                activeFilter = button.dataset.filter || 'all';
                filters.forEach(function (filter) {
                    filter.classList.toggle('active', filter === button);
                });
                applyFaqFilter();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', applyFaqFilter);
        }
    }

    promptChips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var chatPanel = document.getElementById('faqChatPanel');
            var chatInput = document.getElementById('faqChatInput');
            var chatForm = document.getElementById('faqChatForm');

            if (!chatPanel || !chatInput || !chatForm) {
                return;
            }

            chatPanel.hidden = false;
            chatInput.value = chip.dataset.question || chip.textContent.trim();
            chatInput.focus();

            if (chatForm.requestSubmit) {
                chatForm.requestSubmit();
            }
        });
    });
}
