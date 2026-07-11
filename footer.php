                    <?php render_footer_text(); ?>
                </div> <!-- main-content -->
            </div> <!-- container-fluid -->
        </main>
        
        <?php if (defined('CURRENT_USER_ID')): ?>
        <!-- Team Chat Widget -->
        <div id="team-chat-widget" style="position: fixed; bottom: 30px; right: 30px; width: 350px; background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.6); transform: translateY(calc(100% - 60px)); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <!-- Header -->
            <div id="chat-header" class="p-3 d-flex justify-content-between align-items-center" style="cursor: pointer; height: 60px; background: linear-gradient(135deg, #4F46E5 0%, #EC4899 100%); color: white;">
                <h6 class="mb-0 m-0" style="font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1.1rem;"><i class="fa fa-comments me-2"></i> Team Chat</h6>
                <i class="fa fa-chevron-up" id="chat-toggle-icon"></i>
            </div>
            <!-- Messages Area -->
            <div id="chat-messages" class="p-3" style="height: 350px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: transparent;">
                <!-- Messages injected here via JS -->
            </div>
            <!-- Input Area -->
            <div class="p-3 border-top" style="background: rgba(255, 255, 255, 0.5); border-color: rgba(0,0,0,0.05) !important;">
                <form id="chat-form" class="d-flex m-0 align-items-center gap-2">
                    <input type="text" id="chat-input" class="form-control" placeholder="Type a message..." autocomplete="off" required style="border-radius: 20px; background: rgba(255,255,255,0.9);">
                    <button type="submit" class="btn btn-primary" style="border-radius: 50%; width: 42px; height: 42px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa fa-paper-plane" style="margin-left: -2px;"></i></button>
                </form>
            </div>
        </div>

        <style>
            .chat-msg { max-width: 85%; padding: 10px 14px; border-radius: 16px; font-size: 0.9rem; font-family: 'Inter', sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
            .chat-msg.me { background: linear-gradient(135deg, #4F46E5, #4338CA); color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
            .chat-msg.them { background: #ffffff; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 4px; border: 1px solid rgba(0,0,0,0.05); }
            .chat-sender { font-size: 0.75rem; margin-bottom: 4px; opacity: 0.8; font-weight: 500; }
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
