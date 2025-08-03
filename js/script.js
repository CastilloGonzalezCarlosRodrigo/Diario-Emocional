document.addEventListener('DOMContentLoaded', function() {
    // ================ ELEMENTOS DEL DOM ================
    const entryForm = document.getElementById('entryForm');
    const entryText = document.getElementById('entryText');
    const emotionPreview = document.getElementById('emotionPreview');
    const emotionDisplay = document.getElementById('emotionDisplay');
    const confidenceBar = document.getElementById('confidenceBar');
    const userAvatar = document.getElementById('userAvatar');
    const themeToggle = document.getElementById('themeToggle');
    const searchInput = document.getElementById('searchInput');
    const entriesList = document.getElementById('entriesList');
    const currentMonthEl = document.getElementById('currentMonth');
    const calendarDaysEl = document.getElementById('calendarDays');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    const reminderForm = document.getElementById('reminderForm');
    const remindersList = document.getElementById('remindersList');
    
    // ================ NUEVOS ELEMENTOS ================
    const voiceBtn = document.createElement('button');
    voiceBtn.id = 'voiceBtn';
    voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    entryForm.insertBefore(voiceBtn, entryForm.firstChild);

    const suggestionsBox = document.createElement('div');
    suggestionsBox.id = 'suggestionsBox';
    entryText.parentNode.insertBefore(suggestionsBox, entryText.nextSibling);

    // ================ ESTADO DE LA APLICACIÓN ================
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();
    let entries = [];
    let db;

    // ================ DATOS DE CONFIGURACIÓN ================
    const mexicanPhrases = {
        'feliz': ['alegre como lombriz', 'contento como chango', 'feliz como niño con zapatos nuevos'],
        'triste': ['hecho bolita', 'como plato de segunda mesa', 'más solo que perro en boda'],
        'enojado': ['que trino', 'como avispa en tarro', 'hasta la madre'],
        'cansado': ['hecho migajas', 'como trapo viejo', 'sin pilas'],
        'ansioso': ['como agua para chocolate', 'con los nervios de punta', 'como chapulín en comal']
    };

    const emotionKeywords = {
        'Feliz': ['feliz', 'contento', 'alegre', 'bien', 'optimista', 'chingón', 'padre', 'chido'],
        'Triste': ['triste', 'deprimido', 'melancólico', 'desanimado', 'apachurrado', 'bajoneado'],
        'Enojado': ['enojado', 'furioso', 'irritado', 'molesto', 'coraje', 'encabronado', 'emputado'],
        'Ansioso': ['ansioso', 'nervioso', 'preocupado', 'estresado', 'agitado', 'tenso'],
        'Cansado': ['agotado', 'fatigado', 'exhausto', 'sobrecargado', 'quemado', 'fundido'],
        'Amoroso': ['amor', 'cariño', 'ternura', 'afecto', 'enamorado', 'crusheando'],
        'Gratitud': ['agradecido', 'gratitud', 'afortunado', 'bendecido', 'reconocido'],
        'Miedo': ['miedo', 'atemorizado', 'asustado', 'pánico', 'terror', 'angustia'],
        'Orgullo': ['orgulloso', 'logro', 'triunfo', 'éxito', 'realización'],
        'Neutral': ['normal', 'regular', 'indiferente', 'ni fu ni fa']
    };

    const contextKeywords = {
        'trabajo': ['jornada', 'oficina', 'jefe', 'compañeros', 'proyecto', 'reunión'],
        'familia': ['mamá', 'papá', 'hermanos', 'familia', 'hijos', 'esposa', 'esposo'],
        'amor': ['novio', 'novia', 'pareja', 'amor', 'relación', 'cita', 'boda'],
        'salud': ['enfermo', 'doctor', 'hospital', 'dolor', 'medicina', 'terapia']
    };

    // ================ INICIALIZACIÓN ================
    init();

    async function init() {
        initTheme();
        await initDB();
        loadEntries();
        renderCalendar(currentMonth, currentYear);
        setupEventListeners();
        setupReminders();
    }

    // ================ FUNCIONES PRINCIPALES ================
    function setupEventListeners() {
        entryText.addEventListener('input', debounce(function() {
            if (this.value.trim() !== '') {
                detectAndDisplayEmotion(this.value);
                showSuggestions(this.value);
            } else {
                hideEmotionPreview();
                hideSuggestions();
            }
        }, 300));

        voiceBtn.addEventListener('click', startVoiceRecording);
        entryForm.addEventListener('submit', handleEntrySubmit);
        
        themeToggle.addEventListener('click', toggleTheme);
        searchInput.addEventListener('input', debounce(searchEntries, 300));
        
        prevMonthBtn.addEventListener('click', () => navigateMonth(-1));
        nextMonthBtn.addEventListener('click', () => navigateMonth(1));
        
        reminderForm.addEventListener('submit', handleReminderSubmit);
        remindersList.addEventListener('click', handleReminderDelete);
    }

    // ================ FUNCIONALIDADES DEL DIARIO ================
    function detectAndDisplayEmotion(text) {
        const [emotion, confidence, context] = detectEmotion(text);
        updateAvatar(emotion);
        
        emotionDisplay.innerHTML = `
            <span class="emotion-emoji">${getEmotionIcon(emotion)}</span>
            <span class="emotion-name">${emotion}</span>
            ${context ? `<span class="emotion-context">• ${context}</span>` : ''}
        `;
        
        confidenceBar.style.setProperty('--confidence', `${Math.min(100, confidence * 20)}%`);
        confidenceBar.style.backgroundColor = getEmotionColor(emotion);
        showEmotionPreview();
    }

    function detectEmotion(text) {
        const textLower = text.toLowerCase();
        let emotionScores = {};
        let detectedContext = '';
        let intensity = 1;

        if (textLower.includes('muy') || textLower.includes('mucho')) intensity = 1.5;
        if (textLower.includes('demasiado') || textLower.includes('un chingo')) intensity = 2;

        Object.entries(mexicanPhrases).forEach(([emotion, phrases]) => {
            phrases.forEach(phrase => {
                if (textLower.includes(phrase)) {
                    const emotionKey = emotion.charAt(0).toUpperCase() + emotion.slice(1);
                    emotionScores[emotionKey] = (emotionScores[emotionKey] || 0) + 3 * intensity;
                }
            });
        });

        Object.entries(emotionKeywords).forEach(([emotion, keywords]) => {
            keywords.forEach(keyword => {
                if (textLower.includes(keyword)) {
                    emotionScores[emotion] = (emotionScores[emotion] || 0) + 1 * intensity;
                    if (textLower.startsWith(keyword)) emotionScores[emotion] += 1;
                }
            });
        });

        Object.entries(contextKeywords).forEach(([context, keywords]) => {
            if (keywords.some(keyword => textLower.includes(keyword))) {
                detectedContext = context;
            }
        });

        if (Object.keys(emotionScores).length === 0) {
            return ['Neutral', 1, detectedContext];
        }

        const sortedEmotions = Object.entries(emotionScores).sort((a, b) => b[1] - a[1]);
        return [sortedEmotions[0][0], sortedEmotions[0][1], detectedContext];
    }

    async function handleEntrySubmit(e) {
        e.preventDefault();
        const submitButton = entryForm.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span> Guardando...';
        
        try {
            const [emotion, confidence, context] = detectEmotion(entryText.value);
            const entry = {
                text: entryText.value,
                emotion,
                confidence,
                context,
                date: new Date().toISOString(),
                timestamp: Date.now()
            };
            
            await saveEntry(entry);
            
            submitButton.innerHTML = '<i class="fas fa-check"></i> Guardado';
            submitButton.style.backgroundColor = '#4CAF50';
            
            setTimeout(() => {
                entryText.value = '';
                hideEmotionPreview();
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
                submitButton.style.backgroundColor = '';
                loadEntries();
            }, 800);
        } catch (error) {
            console.error('Error al guardar:', error);
            showError('Error al guardar la entrada');
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    }

    // ================ FUNCIONES DE INTERFAZ ================
    function showEmotionPreview() {
        emotionPreview.style.display = 'flex';
        setTimeout(() => {
            emotionPreview.style.opacity = '1';
            emotionPreview.style.transform = 'translateY(0)';
        }, 10);
    }

    function hideEmotionPreview() {
        emotionPreview.style.opacity = '0';
        setTimeout(() => {
            emotionPreview.style.display = 'none';
        }, 300);
    }

    function showSuggestions(text) {
        const textLower = text.toLowerCase();
        suggestionsBox.innerHTML = '';
        
        if (textLower.includes('me siento')) {
            const currentEmotion = Object.keys(mexicanPhrases).find(emotion => 
                textLower.includes(emotion)
            ) || 'feliz';

            mexicanPhrases[currentEmotion].forEach(phrase => {
                const suggestion = document.createElement('div');
                suggestion.className = 'suggestion';
                suggestion.textContent = phrase;
                suggestion.onclick = () => {
                    entryText.value = `Me siento ${phrase}`;
                    hideSuggestions();
                    detectAndDisplayEmotion(entryText.value);
                };
                suggestionsBox.appendChild(suggestion);
            });
            
            suggestionsBox.style.display = 'block';
        } else {
            hideSuggestions();
        }
    }

    function hideSuggestions() {
        suggestionsBox.style.display = 'none';
    }

    function updateAvatar(emotion) {
        const avatars = {
            'Feliz': '😊', 'Triste': '😢', 'Enojado': '😠', 
            'Ansioso': '😰', 'Cansado': '😴', 'Energético': '💪',
            'Amoroso': '❤️', 'Gratitud': '🙏', 'Miedo': '😨',
            'Orgullo': '🦚', 'Neutral': '😐'
        };
        userAvatar.textContent = avatars[emotion] || '😐';
    }

    // ================ BASE DE DATOS ================
    function initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('EmotionDiaryDB', 1);
            request.onerror = () => reject('Error al iniciar la base de datos');
            request.onsuccess = () => {
                db = request.result;
                resolve(db);
            };
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains('entries')) {
                    db.createObjectStore('entries', { keyPath: 'id', autoIncrement: true });
                }
                if (!db.objectStoreNames.contains('reminders')) {
                    db.createObjectStore('reminders', { keyPath: 'id', autoIncrement: true });
                }
            };
        });
    }

    function saveEntry(entry) {
        return new Promise((resolve, reject) => {
            const transaction = db.transaction('entries', 'readwrite');
            const store = transaction.objectStore('entries');
            store.add(entry).onsuccess = () => resolve();
        });
    }

    function loadEntries() {
        return new Promise((resolve, reject) => {
            const transaction = db.transaction('entries', 'readonly');
            const store = transaction.objectStore('entries');
            store.getAll().onsuccess = (e) => {
                entries = e.target.result || [];
                resolve(entries);
            };
        });
    }

    // ================ CALENDARIO ================
    function renderCalendar(month, year) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDay = firstDay.getDay();
        
        currentMonthEl.textContent = new Date(year, month).toLocaleDateString('es', {
            month: 'long',
            year: 'numeric'
        }).toUpperCase();
        
        let calendarHTML = '';
        const weekdays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        weekdays.forEach(day => {
            calendarHTML += `<div class="calendar-weekday">${day}</div>`;
        });
        
        for (let i = 0; i < startingDay; i++) {
            calendarHTML += `<div class="calendar-day empty"></div>`;
        }
        
        const today = new Date();
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const hasEntry = entries.some(entry => entry.date.startsWith(dateStr));
            
            calendarHTML += `
                <div class="calendar-day ${isToday ? 'today' : ''} ${hasEntry ? 'has-entry' : ''}" data-date="${dateStr}">
                    ${day}
                </div>
            `;
        }
        
        calendarDaysEl.innerHTML = calendarHTML;
        
        document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
            day.addEventListener('click', () => {
                const date = day.dataset.date;
                filterEntriesByDate(date);
            });
        });
    }

    function navigateMonth(offset) {
        currentMonth += offset;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        } else if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar(currentMonth, currentYear);
    }

    function filterEntriesByDate(date) {
        const filteredEntries = entries.filter(entry => entry.date.startsWith(date));
        renderEntryList(filteredEntries);
        document.querySelector('.entries-section').scrollIntoView({ behavior: 'smooth' });
    }

    // ================ RECORDATORIOS ================
    function setupReminders() {
        if (!('Notification' in window)) return;
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                loadReminders();
                scheduleDailyReminder();
            }
        });
    }

    function scheduleDailyReminder() {
        const now = new Date();
        const reminderTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 20, 0, 0);
        if (now > reminderTime) {
            reminderTime.setDate(reminderTime.getDate() + 1);
        }
        const timeout = reminderTime - now;
        setTimeout(() => {
            showReminderNotification();
            scheduleDailyReminder();
        }, timeout);
    }

    function showReminderNotification() {
        if (Notification.permission === 'granted') {
            new Notification('📔 Hora de tu Diario Emocional', {
                body: 'Reflexiona sobre cómo te sientes hoy',
                icon: 'assets/icon.png'
            });
        }
    }

    function loadReminders() {
        const transaction = db.transaction('reminders', 'readonly');
        const store = transaction.objectStore('reminders');
        store.getAll().onsuccess = (e) => {
            const reminders = e.target.result || [];
            renderReminders(reminders);
        };
    }

    function renderReminders(reminders) {
        remindersList.innerHTML = reminders.map(reminder => `
            <div class="reminder">
                <span><i class="fas fa-clock"></i> ${reminder.time}</span>
                <button class="delete-reminder" data-id="${reminder.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `).join('');
    }

    async function handleReminderSubmit(e) {
        e.preventDefault();
        const formData = new FormData(reminderForm);
        const time = formData.get('time');
        if (!time) {
            showError('Por favor ingresa una hora');
            return;
        }
        const transaction = db.transaction('reminders', 'readwrite');
        const store = transaction.objectStore('reminders');
        store.add({ time }).onsuccess = () => {
            reminderForm.reset();
            loadReminders();
            showNotification('Recordatorio agregado');
        };
    }

    function handleReminderDelete(e) {
        if (!e.target.closest('.delete-reminder')) return;
        const reminderId = parseInt(e.target.closest('.delete-reminder').dataset.id);
        const transaction = db.transaction('reminders', 'readwrite');
        transaction.objectStore('reminders').delete(reminderId).onsuccess = () => {
            loadReminders();
            showNotification('Recordatorio eliminado');
        };
    }

    // ================ BÚSQUEDA ================
    function searchEntries() {
        const term = searchInput.value.toLowerCase();
        if (!term) {
            renderEntryList(entries);
            return;
        }
        const results = entries.filter(entry => 
            entry.text.toLowerCase().includes(term) || 
            entry.emotion.toLowerCase().includes(term)
        );
        renderEntryList(results);
    }

    function renderEntryList(entriesToShow) {
        entriesList.innerHTML = entriesToShow.map(entry => `
            <div class="entry" data-date="${entry.date.split('T')[0]}">
                <div class="entry-header">
                    <span class="entry-emoji">${getEmotionIcon(entry.emotion)}</span>
                    <span class="entry-date">${formatDate(entry.date)}</span>
                </div>
                <div class="entry-text">${highlightSearchTerms(entry.text, searchInput.value)}</div>
                <div class="entry-emotion" style="color: ${getEmotionColor(entry.emotion)}">
                    ${entry.emotion} (${Math.round(entry.confidence * 20)}%)
                </div>
            </div>
        `).join('');
    }

    function highlightSearchTerms(text, term) {
        if (!term) return text;
        const regex = new RegExp(`(${term})`, 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }

    // ================ TEMA ================
    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 
                         (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        setTheme(savedTheme);
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        updateThemeToggleIcon(theme);
    }

    function updateThemeToggleIcon(theme) {
        themeToggle.innerHTML = `<i class="fas ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>`;
    }

    // ================ UTILIDADES ================
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function getEmotionIcon(emotion) {
        const icons = {
            'Feliz': '😊', 'Triste': '😢', 'Enojado': '😠', 
            'Ansioso': '😰', 'Cansado': '😴', 'Energético': '💪',
            'Amoroso': '❤️', 'Gratitud': '🙏', 'Miedo': '😨',
            'Orgullo': '🦚', 'Neutral': '😐'
        };
        return icons[emotion] || '😐';
    }

    function getEmotionColor(emotion) {
        const colors = {
            'Feliz': '#4CAF50', 'Triste': '#2196F3', 'Enojado': '#F44336',
            'Ansioso': '#FF9800', 'Cansado': '#795548', 'Energético': '#FFEB3B',
            'Amoroso': '#E91E63', 'Gratitud': '#8BC34A', 'Miedo': '#9C27B0',
            'Orgullo': '#FF5722', 'Neutral': '#9E9E9E'
        };
        return colors[emotion] || '#9E9E9E';
    }

    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function showError(message) {
        const error = document.createElement('div');
        error.className = 'error-message';
        error.textContent = message;
        document.body.appendChild(error);
        setTimeout(() => error.classList.add('show'), 10);
        setTimeout(() => {
            error.classList.remove('show');
            setTimeout(() => error.remove(), 300);
        }, 3000);
    }

    // ================ RECONOCIMIENTO DE VOZ ================
    function startVoiceRecording() {
        if (!('webkitSpeechRecognition' in window)) {
            showError('El reconocimiento de voz no es compatible con tu navegador');
            return;
        }
        const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
        recognition.lang = 'es-MX';
        recognition.interimResults = false;

        recognition.onstart = () => {
            voiceBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
            voiceBtn.style.backgroundColor = '#F44336';
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            entryText.value = transcript;
            detectAndDisplayEmotion(transcript);
        };

        recognition.onerror = (event) => {
            showError(`Error de reconocimiento: ${event.error}`);
        };

        recognition.onend = () => {
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            voiceBtn.style.backgroundColor = '';
        };

        recognition.start();
    }
});

// Navegación inferior SPA
document.querySelectorAll('.bottom-nav button').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.app-section').forEach(sec => sec.classList.remove('active'));
        document.querySelectorAll('.bottom-nav button').forEach(b => b.classList.remove('active'));
        
        const target = btn.getAttribute('data-target');
        document.getElementById(`section-${target}`).classList.add('active');
        btn.classList.add('active');
    });
});
