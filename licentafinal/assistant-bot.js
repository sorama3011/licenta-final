// Floating Assistant Bot JavaScript
class AssistantBot {
    constructor() {
        this.isOpen = false;
        this.currentAnswer = null;
        this.isConnecting = false;
        this.questions = {
            'order-status': {
                question: 'Unde este comanda mea?',
                answer: 'Poți urmări comanda ta din secțiunea <strong>Contul Meu > Istoric comenzi</strong>. Acolo vei găsi statusul actualizat și numărul de urmărire pentru fiecare comandă. De asemenea, vei primi un email de confirmare cu detaliile de urmărire.',
                hasButton: true,
                buttonText: 'Mergi la Istoric comenzi',
                buttonIcon: 'bi-clock-history',
                buttonAction: () => this.goToOrderHistory()
            },
            'returns': {
                question: 'Cum returnez un produs?',
                answer: 'Din motive de igienă și siguranță alimentară, produsele alimentare nu pot fi returnate după livrare. Excepții fac doar cazurile în care produsul este deteriorat la livrare sau nu corespunde descrierii. În aceste situații, te rugăm să ne contactezi în 24 de ore.'
            },
            'delivery-area': {
                question: 'Livrați și în zona mea?',
                answer: 'Livrăm în toată România! <strong>București:</strong> 24-48 ore, <strong>Orașe mari:</strong> 2-3 zile, <strong>Restul țării:</strong> 3-5 zile lucrătoare. Transport gratuit pentru comenzi peste 100 RON. Pentru produse perisabile folosim transport refrigerat.'
            },
            'payment-methods': {
                question: 'Ce metode de plată acceptați?',
                answer: 'Acceptăm <strong>card de credit/debit</strong> (Visa, Mastercard), <strong>transfer bancar</strong> și <strong>plata ramburs</strong> (cu taxă suplimentară de 5 RON). Toate plățile online sunt securizate și procesate prin partenerii noștri certificați.'
            },
            'loyalty-points': {
                question: 'Am puncte de fidelitate – cum le folosesc?',
                answer: 'Punctele tale de fidelitate pot fi folosite pentru reduceri: <strong>100 puncte = 5% reducere</strong>, <strong>200 puncte = 10% reducere</strong>, <strong>500 puncte = 20% reducere</strong>. Aplică reducerea la finalizarea comenzii din secțiunea "Contul Meu".'
            }
        };
        
        this.init();
    }

    init() {
        this.createBotHTML();
        this.attachEventListeners();
        this.showWelcomeNotification();
    }

    createBotHTML() {
        const botHTML = `
            <div class="assistant-bot">
                <!-- Bot Icon -->
                <div class="bot-icon" id="botIcon">
                    <i class="bi bi-robot"></i>
                    <div class="bot-notification" id="botNotification" style="display: none;">!</div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel" id="chatPanel">
                    <div class="chat-header">
                        <h5><i class="bi bi-robot me-2"></i>Asistent Gusturi Românești</h5>
                        <button class="chat-close" id="chatClose">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="chat-body">
                        <div class="greeting-message">
                            Salut! Cu ce te pot ajuta azi? 😊<br>
                            <small class="text-muted">Selectează o întrebare de mai jos:</small>
                        </div>
                        
                        <div class="question-buttons">
                            ${Object.entries(this.questions).map(([key, data]) => 
                                `<button class="question-btn" data-question="${key}">
                                    ${data.question}
                                </button>`
                            ).join('')}
                        </div>
                        
                        <div class="answer-section" id="answerSection">
                            <div id="answerContent"></div>
                            <div id="answerButtons" class="mt-3"></div>
                            <div class="mt-3">
                                <button class="btn btn-outline-primary btn-sm" onclick="assistantBot.showQuestions()">
                                    <i class="bi bi-arrow-left me-1"></i>Înapoi la întrebări
                                </button>
                                <button class="btn btn-primary btn-sm ms-2" id="contactButton" onclick="assistantBot.initiateContact()">
                                    <i class="bi bi-envelope me-1"></i>Contactează-ne
                                </button>
                            </div>
                        </div>

                        <!-- Live Chat Connection Section -->
                        <div class="live-chat-section" id="liveChatSection" style="display: none;">
                            <div class="connecting-message" id="connectingMessage">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span>Te conectăm cu un operator...</span>
                                </div>
                            </div>
                            <div class="operator-busy-message" id="operatorBusyMessage" style="display: none;">
                                <div class="alert alert-warning mb-3">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Momentan toți operatorii sunt ocupați. Te rugăm lasă un mesaj folosind formularul de contact.
                                </div>
                                <button class="btn btn-primary btn-sm w-100" onclick="window.location.href='contact.html'">
                                    <i class="bi bi-envelope me-1"></i>Deschide formularul de contact
                                </button>
                                <button class="btn btn-outline-secondary btn-sm w-100 mt-2" onclick="assistantBot.showQuestions()">
                                    <i class="bi bi-arrow-left me-1"></i>Înapoi la întrebări
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', botHTML);
    }

    attachEventListeners() {
        const botIcon = document.getElementById('botIcon');
        const chatPanel = document.getElementById('chatPanel');
        const chatClose = document.getElementById('chatClose');
        const questionButtons = document.querySelectorAll('.question-btn');

        // Toggle chat panel
        botIcon.addEventListener('click', () => this.toggleChat());
        chatClose.addEventListener('click', () => this.closeChat());

        // Question buttons
        questionButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const questionKey = e.target.getAttribute('data-question');
                this.showAnswer(questionKey);
            });
        });

        // Close chat when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !chatPanel.contains(e.target) && !botIcon.contains(e.target)) {
                this.closeChat();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeChat();
            }
        });
    }

    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    openChat() {
        const chatPanel = document.getElementById('chatPanel');
        const botNotification = document.getElementById('botNotification');
        
        chatPanel.classList.add('active');
        this.isOpen = true;
        
        // Hide notification dot
        botNotification.style.display = 'none';
        
        // Reset to questions view
        this.showQuestions();
        
        // Analytics (if needed)
        this.trackEvent('chat_opened');
    }

    closeChat() {
        const chatPanel = document.getElementById('chatPanel');
        chatPanel.classList.remove('active');
        this.isOpen = false;
        
        // Reset any ongoing connections
        this.isConnecting = false;
        
        // Analytics (if needed)
        this.trackEvent('chat_closed');
    }

    showAnswer(questionKey) {
        const question = this.questions[questionKey];
        if (!question) return;

        const answerSection = document.getElementById('answerSection');
        const answerContent = document.getElementById('answerContent');
        const answerButtons = document.getElementById('answerButtons');
        const questionButtons = document.querySelector('.question-buttons');
        const liveChatSection = document.getElementById('liveChatSection');

        // Hide other sections
        questionButtons.style.display = 'none';
        liveChatSection.style.display = 'none';

        // Show answer content
        answerContent.innerHTML = question.answer;

        // Add specific action button if available
        if (question.hasButton) {
            answerButtons.innerHTML = `
                <button class="btn btn-primary btn-sm mb-2" onclick="assistantBot.questions['${questionKey}'].buttonAction()">
                    <i class="${question.buttonIcon} me-1"></i>${question.buttonText}
                </button>
            `;
        } else {
            answerButtons.innerHTML = '';
        }

        answerSection.classList.add('show');
        this.currentAnswer = questionKey;
        
        // Analytics
        this.trackEvent('question_clicked', { question: questionKey });
    }

    showQuestions() {
        const answerSection = document.getElementById('answerSection');
        const questionButtons = document.querySelector('.question-buttons');
        const liveChatSection = document.getElementById('liveChatSection');

        // Hide other sections
        answerSection.classList.remove('show');
        liveChatSection.style.display = 'none';
        
        // Show questions
        questionButtons.style.display = 'flex';
        
        this.currentAnswer = null;
        this.isConnecting = false;
    }

    goToOrderHistory() {
        // Check if user is logged in
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        
        if (userData.loggedIn) {
            // User is logged in, go to account page with order history anchor
            window.location.href = 'account.html#order-history';
        } else {
            // User not logged in, redirect to login page
            localStorage.setItem('redirectAfterLogin', 'account.html#order-history');
            window.location.href = 'login.html';
        }
        
        // Analytics
        this.trackEvent('order_history_clicked');
    }

    initiateContact() {
        if (this.isConnecting) return;

        const questionButtons = document.querySelector('.question-buttons');
        const answerSection = document.getElementById('answerSection');
        const liveChatSection = document.getElementById('liveChatSection');
        const connectingMessage = document.getElementById('connectingMessage');
        const operatorBusyMessage = document.getElementById('operatorBusyMessage');

        // Hide other sections
        questionButtons.style.display = 'none';
        answerSection.classList.remove('show');

        // Show live chat section with connecting message
        liveChatSection.style.display = 'block';
        connectingMessage.style.display = 'block';
        operatorBusyMessage.style.display = 'none';

        this.isConnecting = true;

        // Simulate connection attempt
        setTimeout(() => {
            if (this.isConnecting) {
                // Show operator busy message
                connectingMessage.style.display = 'none';
                operatorBusyMessage.style.display = 'block';
                this.isConnecting = false;
                
                // Analytics
                this.trackEvent('live_chat_attempted');
            }
        }, 1500); // 1.5 seconds delay
    }

    showWelcomeNotification() {
        // Show notification dot after 5 seconds if chat hasn't been opened
        setTimeout(() => {
            if (!this.isOpen) {
                const botNotification = document.getElementById('botNotification');
                if (botNotification) {
                    botNotification.style.display = 'flex';
                }
            }
        }, 5000);
    }

    // Analytics tracking (optional)
    trackEvent(eventName, data = {}) {
        // This can be connected to Google Analytics or other tracking services
        console.log('Bot Event:', eventName, data);
    }

    // Public method to add new questions dynamically
    addQuestion(key, question, answer, hasButton = false, buttonText = '', buttonIcon = '', buttonAction = null) {
        this.questions[key] = { 
            question, 
            answer, 
            hasButton, 
            buttonText, 
            buttonIcon, 
            buttonAction 
        };
        // Refresh the bot interface if needed
    }

    // Public method to show notification
    showNotification() {
        const botNotification = document.getElementById('botNotification');
        if (botNotification) {
            botNotification.style.display = 'flex';
        }
    }

    // Public method to hide notification
    hideNotification() {
        const botNotification = document.getElementById('botNotification');
        if (botNotification) {
            botNotification.style.display = 'none';
        }
    }
}

// Initialize the assistant bot when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if not already initialized
    if (!window.assistantBot) {
        window.assistantBot = new AssistantBot();
    }
});

// Export for use in other scripts
window.AssistantBot = AssistantBot;