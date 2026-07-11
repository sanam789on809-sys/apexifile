                    <?php render_footer_text(); ?>
                </div> <!-- main-content -->
            </div> <!-- container-fluid -->
        </main>
        
        <?php if (defined('CURRENT_USER_ID')): ?>
        <!-- Team Chat Widget -->
        <div id="team-chat-widget" style="position: fixed; bottom: 20px; right: 20px; width: 350px; background: #fff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--bs-border-color); transform: translateY(calc(100% - 50px)); transition: transform 0.3s ease;">
            <!-- Header -->
            <div id="chat-header" class="bg-primary text-white p-3 d-flex justify-content-between align-items-center" style="cursor: pointer; height: 50px;">
                <h6 class="mb-0 m-0"><i class="fa fa-comments me-2"></i> Team Chat</h6>
                <i class="fa fa-chevron-up" id="chat-toggle-icon"></i>
            </div>
            <!-- Messages Area -->
            <div id="chat-messages" class="p-3 bg-light" style="height: 350px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                <!-- Messages injected here via JS -->
            </div>
            <!-- Input Area -->
            <div class="p-3 bg-white border-top">
                <form id="chat-form" class="d-flex m-0">
                    <input type="text" id="chat-input" class="form-control me-2" placeholder="Type a message..." autocomplete="off" required>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i></button>
                </form>
            </div>
        </div>

        <style>
            .chat-msg { max-width: 85%; padding: 8px 12px; border-radius: 15px; font-size: 0.9rem; }
            .chat-msg.me { background: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 5px; }
            .chat-msg.them { background: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 5px; }
            .chat-sender { font-size: 0.75rem; margin-bottom: 2px; opacity: 0.8; }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const widget = document.getElementById('team-chat-widget');
                const header = document.getElementById('chat-header');
                const icon = document.getElementById('chat-toggle-icon');
                const messagesDiv = document.getElementById('chat-messages');
                const form = document.getElementById('chat-form');
                const input = document.getElementById('chat-input');
                let isChatOpen = false;
                let lastMessageCount = 0;

                header.addEventListener('click', () => {
                    isChatOpen = !isChatOpen;
                    if (isChatOpen) {
                        widget.style.transform = 'translateY(0)';
                        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    } else {
                        widget.style.transform = 'translateY(calc(100% - 50px))';
                        icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                    }
                });

                function fetchMessages() {
                    fetch('chat-ajax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=fetch'
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            if (data.messages.length > lastMessageCount) {
                                let html = '';
                                data.messages.forEach(msg => {
                                    const cls = msg.is_me ? 'me' : 'them';
                                    const sender = msg.is_me ? 'You' : msg.sender;
                                    html += `<div class="chat-msg ${cls}">
                                                <div class="chat-sender">${sender} &bull; ${msg.time}</div>
                                                <div>${msg.message}</div>
                                             </div>`;
                                });
                                messagesDiv.innerHTML = html;
                                
                                // Only auto-scroll if we were already at bottom or if it's our message
                                messagesDiv.scrollTop = messagesDiv.scrollHeight;
                                lastMessageCount = data.messages.length;
                            }
                        }
                    });
                }

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const msg = input.value.trim();
                    if (!msg) return;
                    
                    fetch('chat-ajax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=send&message=' + encodeURIComponent(msg)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            input.value = '';
                            fetchMessages();
                        }
                    });
                });

                // Fetch immediately, then every 3 seconds
                fetchMessages();
                setInterval(fetchMessages, 3000);
            });
        </script>
        <?php endif; ?>

        <?php
            // Global MaterialPro Polish
        ?>
        <style>
            /* Global Fade-in Animation */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .main-content {
                animation: fadeIn 0.4s ease-out forwards;
            }

            /* Improved Form Elements */
            .form-control, .form-select {
                border-radius: 8px !important;
                border: 1px solid #ced4da;
                transition: all 0.2s ease;
                box-shadow: none !important;
            }
            .form-control:focus, .form-select:focus {
                border-color: #0d6efd;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15) !important;
            }
            
            /* Sleeker Buttons */
            .btn {
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.2s;
            }
            .btn:active {
                transform: scale(0.98);
            }

            /* Card Polish */
            .ps-card {
                border: none;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                border-radius: 12px;
                background: #fff;
            }
        </style>

        <?php
            render_json_variables();
            
            render_assets('js', 'footer');
            render_assets('css', 'footer');

            render_custom_assets('body_bottom');
        ?>
    </body>
</html>
<?php
    if ( DEBUG === true ) {
        // echo "\n" . '<!-- DEBUG INFORMATION' . "\n";
        // echo "\n" . '-->' . "\n" ;
    }

    ob_end_flush();
