                let isMusicOn = true, selectedOptionIndex = null, currentTask = null;
let musicContext = null, musicTimer = null, musicStep = 0;
let gameOver = false;
let isResolvingTurn = false;
let answerSubmitted = false;
let selectedSetupPlayerCount = 1;
let pendingEffectAction = null;
let activeMiniGame = null;
const avatarChoices = ['🎃', '🦇', '🧙', '🧙‍♀️', '🐱', '🧛', '🧟', '🧚', '🧜', '🦊', '🐼', '🐰', '🐲', 'stitch', '🧱', '👩‍🌾', 'creeper', 'angel'];
const avatarUnlockCosts = { '🎃': 0, '🦇': 25, '🧙': 50, '🧙‍♀️': 80, '🐱': 100, '🧛': 125, '🧟': 150, '🧚': 175, '🧜': 200, '🦊': 225, '🐼': 250, '🐰': 275, '🐲': 300, stitch: 325, '🧱': 350, '👩‍🌾': 375, creeper: 400, angel: 425 };
const avatarLabels = { stitch: 'Стіч', '🧱': 'Стів', '👩‍🌾': 'Алекс', creeper: 'Кріпер', angel: 'Ангел' };
const accessoryChoices = [
    { id: 'head-none', slot: 'head', emoji: '', label: 'Без головного убору', cost: 0 },
    { id: 'hat', slot: 'head', emoji: '🎩', label: 'Капелюшок', cost: 20 },
    { id: 'witch-hat', slot: 'head', emoji: '🧙‍♀️', label: 'Відьомський капелюх', cost: 35 },
    { id: 'crown', slot: 'head', emoji: '👑', label: 'Корона', cost: 60 },
    { id: 'flower', slot: 'head', emoji: '🌸', label: 'Квіточка', cost: 25 },
    { id: 'glasses-none', slot: 'eyes', emoji: '', label: 'Без окулярів', cost: 0 },
    { id: 'glasses', slot: 'eyes', emoji: '🕶️', label: 'Окуляри', cost: 40 },
    { id: 'round-glasses', slot: 'eyes', emoji: '🤓', label: 'Круглі окуляри', cost: 45 },
    { id: 'star-glasses', slot: 'eyes', emoji: '🤩', label: 'Зіркові окуляри', cost: 70 },
    { id: 'bag-none', slot: 'shoulder', emoji: '', label: 'Без сумки', cost: 0 },
    { id: 'backpack', slot: 'shoulder', emoji: '🎒', label: 'Рюкзак', cost: 30 },
    { id: 'handbag', slot: 'shoulder', emoji: '👜', label: 'Сумочка', cost: 50 },
    { id: 'body-none', slot: 'body', emoji: '', label: 'Без одягу', cost: 0 },
    { id: 'scarf', slot: 'body', emoji: '🧣', label: 'Шарфик', cost: 25 },
    { id: 'cape', slot: 'body', emoji: '🦸', label: 'Супер-плащ', cost: 65 },
    { id: 'tshirt', slot: 'body', emoji: '👕', label: 'Футболка', cost: 40 }
];
let selectedAvatar = '🎃';
let selectedAccessories = {};
let selectedAccessory = ''; // сумісність зі старими профілями
let profileTargetIndex = null;
let profileProgress = { stars: 0, unlocked: [0], players: {} };
try { profileProgress = JSON.parse(localStorage.getItem('englishBoardProfile') || 'null') || profileProgress; } catch (error) {}
if (!profileProgress.players || typeof profileProgress.players !== 'object') profileProgress.players = {};
function getSavedPlayerStars(name, fallback = 0) {
    return Number(profileProgress.players[name]?.stars ?? fallback) || 0;
}
function saveProfileProgress(player) {
    if (player && !player.isBot) {
        profileProgress.players[player.name] = {
            stars: Number(player.stars) || 0,
            accessories: { ...(player.accessories || {}) },
            avatar: player.avatar
        };
        profileProgress.stars = Number(player.stars) || 0;
    }
    localStorage.setItem('englishBoardProfile', JSON.stringify(profileProgress));
}
function getAvatarMarkup(player, compact = false) {
    const accessories = player.accessories || {};
    const baseAvatar = ['angel', 'stitch', '🧸', 'creeper', '🟩'].includes(player.avatar)
        ? `<img class="avatar-image" src="img/${player.avatar === 'angel' ? 'Angel' : player.avatar === 'creeper' || player.avatar === '🟩' ? 'criper' : 'Stich'}.png" alt="${player.avatar === 'angel' ? 'Ангел' : player.avatar === 'creeper' || player.avatar === '🟩' ? 'Кріпер' : 'Стіч'}">`
        : player.avatar;
    const layers = accessoryChoices
        .filter(item => item.emoji && accessories[item.slot] === item.id)
        .map(item => `<span class="avatar-accessory accessory-${item.slot}" aria-hidden="true">${item.emoji}</span>`)
        .join('');
    return `<span class="avatar-character">${baseAvatar}</span>${layers}`;
}
function getAvatarChoiceMarkup(avatar) {
    if (avatar === 'angel') return '<img class="avatar-choice-image" src="img/Angel.png" alt="Ангел">';
    if (avatar === 'stitch') return '<img class="avatar-choice-image" src="img/Stich.png" alt="Стіч">';
    if (avatar === 'creeper') return '<img class="avatar-choice-image" src="img/criper.png" alt="Кріпер">';
    return avatar;
}
function getAvatarText(player) { return `${player.avatar}${Object.values(player.accessories || {}).join('')}`; }
function renderProfilePickers() {
    const avatars = document.getElementById('avatarOptions');
    const accessories = document.getElementById('accessoryOptions');
    if (!avatars || !accessories) return;
    const profilePlayer = typeof players !== 'undefined'
        ? (profileTargetIndex !== null ? players[profileTargetIndex] : players[0])
        : null;
    const playerStars = profilePlayer && !profilePlayer.isBot ? Number(profilePlayer.stars) || 0 : 0;
    avatars.innerHTML = avatarChoices.map(avatar => {
        const cost = avatarUnlockCosts[avatar] || 0;
        const unlocked = playerStars >= cost;
        return `<button type="button" class="avatar-choice ${selectedAvatar === avatar ? 'active' : ''} ${unlocked ? '' : 'locked'}" ${unlocked ? `onclick="selectAvatar('${avatar}')"` : 'disabled'}><span class="avatar-preview">${getAvatarChoiceMarkup(avatar)}${unlocked ? '' : '<b class="locked-badge">🔒</b>'}</span><small>${avatarLabels[avatar] ? `${avatarLabels[avatar]} · ` : ''}${cost ? `${cost} ⭐` : 'Вільний'}</small></button>`;
    }).join('');
    accessories.innerHTML = accessoryChoices.map(item => {
        const unlocked = playerStars >= item.cost;
        const active = selectedAccessories[item.slot] === item.id;
        return `<button type="button" class="accessory-choice accessory-slot-${item.slot} ${active ? 'active' : ''} ${unlocked ? '' : 'locked'}" ${unlocked ? `onclick="selectAccessory('${item.id}')"` : 'disabled'}><span>${unlocked ? (item.emoji || '✨') : '🔒'}</span><small>${item.label}${item.cost ? ` · ${item.cost} ⭐` : ''}</small></button>`;
    }).join('');
    const slotLabels = { head: 'На голову', eyes: 'На очі', shoulder: 'На плече', body: 'На тіло' };
    accessories.innerHTML = Object.keys(slotLabels).map(slot => {
        const slotItems = accessoryChoices.filter(item => item.slot === slot).map(item => {
            const unlocked = playerStars >= item.cost;
            const active = selectedAccessories[item.slot] === item.id;
            return `<button type="button" class="accessory-choice ${active ? 'active' : ''} ${unlocked ? '' : 'locked'}" ${unlocked ? `onclick="selectAccessory('${item.id}')"` : 'disabled'}><span class="accessory-preview">${item.emoji || '✨'}${unlocked ? '' : '<b class="locked-badge">🔒</b>'}</span><small>${item.label}${item.cost ? ` · ${item.cost} ⭐` : ''}</small></button>`;
        }).join('');
        return `<div class="accessory-category"><div class="accessory-category-title">${slotLabels[slot]}</div><div class="accessory-category-options">${slotItems}</div></div>`;
    }).join('');
    document.getElementById('profileStars').innerText = `⭐ Зірочки: ${playerStars}`;
}
function selectAvatar(avatar) {
    const profilePlayer = profileTargetIndex !== null ? players[profileTargetIndex] : players[0];
    if ((Number(profilePlayer?.stars) || 0) < (avatarUnlockCosts[avatar] || 0)) return;
    selectedAvatar = avatar;
    if (profileTargetIndex !== null && players[profileTargetIndex] && !players[profileTargetIndex].isBot) {
        players[profileTargetIndex].avatar = avatar;
        saveProfileProgress(players[profileTargetIndex]);
        renderScoreboard();
    }
    renderProfilePickers();
}
function selectAccessory(accessory) {
    const item = accessoryChoices.find(option => option.id === accessory);
    if (!item) return;
    selectedAccessories[item.slot] = item.id;
    if (profileTargetIndex !== null && players[profileTargetIndex] && !players[profileTargetIndex].isBot) {
        players[profileTargetIndex].accessories = { ...selectedAccessories };
        saveProfileProgress(players[profileTargetIndex]);
        renderScoreboard();
    }
    renderProfilePickers();
}
function openProfileModal(playerIndex) {
    const player = players[playerIndex];
    if (!player || player.isBot) return;
    profileTargetIndex = playerIndex;
    selectedAvatar = player.avatar;
    selectedAccessories = { ...(player.accessories || {}) };
    document.getElementById('profileModalPlayerName').innerText = player.name;
    renderProfilePickers();
    document.getElementById('profileModal').classList.add('active');
}
function closeProfileModal() {
    document.getElementById('profileModal')?.classList.remove('active');
    profileTargetIndex = null;
}
function openInstructions() { document.getElementById('instructionsModal')?.classList.add('active'); }
function closeInstructions() { document.getElementById('instructionsModal')?.classList.remove('active'); }
const ukrainianTranslations = {
    pumpkin: 'гарбуз', ghost: 'привид', witch: 'відьма', skeleton: 'скелет', monster: 'монстр',
    pencil: 'олівець', book: 'книга', pen: 'ручка', rubber: 'гумка', rollerblades: 'ролики',
    bike: 'велосипед', train: 'потяг', car: 'автомобіль', bus: 'автобус', fast: 'швидкий',
    slow: 'повільний', small: 'маленький', big: 'великий', sad: 'сумний', happy: 'щасливий',
    winner: 'переможець', king: 'король', pirate: 'пірат', prize: 'приз', crown: 'корона',
    box: 'коробка', kite: 'повітряний змій', radio: 'радіо', toy: 'іграшка', sunny: 'сонячно',
    cloudy: 'хмарно', rainy: 'дощово', 'computer game': 'комп’ютерна гра', new: 'новий', old: 'старий',
    swan: 'лебідь', duck: 'качка', frog: 'жаба', chicken: 'курка', clown: 'клоун', cowboy: 'ковбой',
    grandpa: 'дідусь', clothes: 'одяг', spy: 'шпигун', dancer: 'танцюрист'
};
function translateAnswer(answer) { return ukrainianTranslations[answer] || answer; }
function playHalloweenNote() {
    if (!musicContext || !isMusicOn) return;
    const notes = [146.83, 174.61, 220, 174.61, 130.81, 164.81, 196, 164.81];
    const oscillator = musicContext.createOscillator();
    const gain = musicContext.createGain();
    const now = musicContext.currentTime;
    oscillator.type = 'triangle';
    oscillator.frequency.value = notes[musicStep++ % notes.length];
    gain.gain.setValueAtTime(0.0001, now);
    gain.gain.exponentialRampToValueAtTime(0.018, now + 0.04);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.52);
    oscillator.connect(gain).connect(musicContext.destination);
    oscillator.start(now);
    oscillator.stop(now + 0.55);
}
function startHalloweenMusic() {
    if (!isMusicOn || musicTimer) return;
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    if (!musicContext) musicContext = new AudioContext();
    musicContext.resume();
    musicStep = 0;
    playHalloweenNote();
    musicTimer = setInterval(playHalloweenNote, 620);
}
function stopHalloweenMusic() {
    if (musicTimer) { clearInterval(musicTimer); musicTimer = null; }
    if (musicContext && musicContext.state === 'running') musicContext.suspend();
}
function toggleSound() {
    isMusicOn = !isMusicOn;
    const soundButton = document.getElementById('soundToggleBtn');
    soundButton.innerText = isMusicOn ? '🎵' : '🔇';
    soundButton.setAttribute('aria-label', isMusicOn ? 'Музика: увімкнена' : 'Музика: вимкнена');
    soundButton.title = isMusicOn ? 'Музика: увімкнена' : 'Музика: вимкнена';
    if (isMusicOn) startHalloweenMusic(); else stopHalloweenMusic();
}
function speakWord(text) {
      const cleanText = text.replace(/[^a-zA-Z0-9\s]/g, '').trim();
    if (!cleanText) return;
      const audio = new Audio(`https://translate.google.com/translate_tts?ie=UTF-8&q=${encodeURIComponent(cleanText)}&tl=en&client=tw-ob`);
    audio.play().catch(() => {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
          const u = new SpeechSynthesisUtterance(cleanText);
        u.lang = 'en-US';
        window.speechSynthesis.speak(u);
    }
      });
}
function speakTask(text) {
    if (!text) return;
    let fallbackStarted = false;
    const useSpeechSynthesis = () => {
        if (fallbackStarted || !('speechSynthesis' in window)) return;
        fallbackStarted = true;
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'uk-UA';
        utterance.rate = 0.95;
        window.speechSynthesis.speak(utterance);
    };
    const audio = new Audio(`https://translate.google.com/translate_tts?ie=UTF-8&q=${encodeURIComponent(text)}&tl=uk&client=tw-ob`);
    audio.preload = 'auto';
    audio.onerror = useSpeechSynthesis;
    audio.play().catch(useSpeechSynthesis);
    // Якщо сервер TTS не відповідає, не залишаємо гравця без озвучення.
    setTimeout(() => {
        if (audio.readyState < 2) useSpeechSynthesis();
    }, 1200);
}

function speakAnswerFeedback(isCorrect, answer) {
    const text = isCorrect
        ? 'Правильно! Молодець!'
        : `Неправильно. Правильна відповідь: ${answer}.`;
    speakTask(text);
}

    const vocabulary = [
      ['pumpkin','🎃','гарбуз'], ['ghost','👻','привид'], ['witch','🧙','відьма'], ['skeleton','💀','скелет'], ['monster','👹','монстр'],
      ['school','🏫','школа'], ['bag','🎒','рюкзак'], ['pencil','✏️','олівець'], ['pen','🖊️','ручка'], ['rubber','🧽','гумка'], ['book','📗','книга'],
      ['toy','🧸','іграшка'], ['rollerblades','🛼','ролики'], ['train','🚂','потяг'], ['slow','🐢','повільний'], ['fast','🐕','швидкий'], ['winner','🏃','переможець'],
      ['prize','🏆','приз'], ['robot','🤖','робот'], ['birthday','🎂','день народження'], ['cake','🍰','торт'], ['present','🎁','подарунок'], ['watch','⌚','годинник'],
      ['kite','🪁','повітряний змій'], ['cloudy','☁️','хмарно'], ['computer game','🎮','комп’ютерна гра'], ['bike','🚲','велосипед'], ['radio','📻','радіо'], ['new','✨','новий'], ['old','📜','старий'],
      ['sunny','🌞','сонячно'], ['family','👨‍👩‍👧‍👦','сім’я'], ['mum','👩','мама'], ['dad','👨','тато'], ['brother','👦','брат'], ['sister','👧','сестра'],
      ['spell','🔤','писати по літерах'], ['chair','🪑','стілець'], ['write','✍️','писати'], ['board','🟩','дошка'], ['be quiet','🤫','тихо'], ['window','🪟','вікно'],
      ['stand up','🧍','встати'], ['open','🚪','відкрити'], ['sit down','🪑','сісти'], ['car','🚗','автомобіль'], ['ball','⚽','м’яч'], ['doll','🪆','лялька'],
      ['stickers','🌟','наклейки'], ['crayon','🖍️','восковий олівець'], ['card','💌','листівка'], ['frog','🐸','жаба'], ['teacher','👩‍🏫','вчитель'], ['close','🚪','закрити'], ['door','🚪','двері'],
      ['big','🐘','великий'], ['small','🐥','маленький'], ['sad','😢','сумний'], ['swan','🦢','лебідь'], ['duck','🦆','качка'], ['happy','😊','щасливий'],
      ['pirate','🏴‍☠️','пірат'], ['clown','🤡','клоун'], ['king','🤴','король'], ['crown','👑','корона'], ['cowboy','🤠','ковбой'], ['box','📦','коробка'], ['clothes','👕','одяг'], ['spy','🕵️','шпигун'], ['dancer','💃','танцюрист'],
      ['book','📗','книга'], ['pen','🖊️','ручка'], ['family','👨‍👩‍👧‍👦','сім’я'], ['cake','🍰','торт'], ['present','🎁','подарунок'], ['window','🪟','вікно'], ['door','🚪','двері']
    ];
    vocabulary.forEach(([word, icon, translation]) => { ukrainianTranslations[word] = translation; });
    // Для слова fast використовуємо символ швидкості, а не собаку.
    const fastVocabularyItem = vocabulary.find(([word]) => word === 'fast');
    if (fastVocabularyItem) fastVocabularyItem[1] = '💨';
    const questionTemplates = [
      word => `Що це?`,
      word => `Обери англійське слово: ${translateAnswer(word)}`,
      word => `Напиши по літерах: ${word}`,
      word => `Яке слово означає «${translateAnswer(word)}»?`,
      word => `Скажи англійською: ${translateAnswer(word)}`
    ];
    questionTemplates[2] = word => `Напиши англійське слово: ${word}`;
    const taskAnimations = {
      pumpkin: 'pumpkin', ghost: 'float', witch: 'spell', skeleton: 'rattle', monster: 'stomp',
      school: 'pop', bag: 'bounce', pencil: 'draw', pen: 'draw', rubber: 'squish', book: 'open',
      toy: 'bounce', rollerblades: 'roll', train: 'roll', slow: 'creep', fast: 'dash', winner: 'dash',
      prize: 'sparkle', robot: 'robot', birthday: 'confetti', cake: 'bounce', present: 'pop',
      watch: 'tick', kite: 'float', cloudy: 'float', 'computer game': 'game', bike: 'roll',
      radio: 'music', new: 'sparkle', old: 'sway', sunny: 'shine', family: 'gather',
      mum: 'wave', dad: 'wave', brother: 'wave', sister: 'wave', spell: 'draw', chair: 'sway',
      write: 'draw', board: 'shine', 'be quiet': 'shush', window: 'open', 'stand up': 'rise',
      open: 'open', 'sit down': 'sit', car: 'roll', ball: 'bounce', doll: 'wave',
      stickers: 'sparkle', crayon: 'draw', card: 'open', frog: 'hop', teacher: 'wave',
      close: 'open', door: 'open', big: 'grow', small: 'shrink', sad: 'droop',
      swan: 'float', duck: 'waddle', happy: 'dance', pirate: 'sway', clown: 'bounce',
      king: 'wave', crown: 'sparkle', cowboy: 'sway', box: 'open', clothes: 'sway',
      spy: 'sneak', dancer: 'dance'
    };
    const baseBoardTasks = Array.from({ length: 100 }, (_, num) => {
      if (num === 0) return { num, title: 'START', icon: '🚀', type: 'info' };
      if (num === 99) return { num, title: 'FINISH', icon: '🏆', type: 'info' };
      if (num === 15 || num === 45 || num === 75) return { num, title: 'Go +3', icon: '➡️', type: 'forward', val: 3 };
      if (num === 30 || num === 60 || num === 90) return { num, title: 'Go back 2', icon: '⬅️', type: 'back', val: 2 };
      if (num === 10 || num === 50 || num === 80) return { num, title: 'Miss turn', icon: '🛑', type: 'miss' };
      const [word, icon] = num === 20
        ? vocabulary.find(([itemWord]) => itemWord === 'cake')
        : vocabulary[(num - 1) % vocabulary.length];
      const distractors = [
        vocabulary[(num + 7) % vocabulary.length][0],
        vocabulary[(num + 17) % vocabulary.length][0]
      ];
      const choices = [word, ...distractors];
      const shift = num % choices.length;
      return {
        num, title: word, icon,
        video: ({ pumpkin: 'anime/1.mp4', ghost: 'anime/ghost.mp4', school: 'anime/schol.mp4' })[word] || '',
        animation: taskAnimations[word] || 'bounce',
        prompt: questionTemplates[num % questionTemplates.length](word),
        answer: word,
        requiresTyping: num % questionTemplates.length === 2,
        options: choices.slice(shift).concat(choices.slice(0, shift))
      };
    });
    const miniGameTasks = [
        { title: 'Match game', icon: '🧩', type: 'minigame', game: 'match' },
        { title: 'Word puzzle', icon: '🔤', type: 'minigame', game: 'scramble' },
        { title: 'Mini maze', icon: '🌀', type: 'minigame', game: 'maze' },
        { title: 'Match game', icon: '🧩', type: 'minigame', game: 'match' },
        { title: 'Word puzzle', icon: '🔤', type: 'minigame', game: 'scramble' },
        { title: 'Mini maze', icon: '🌀', type: 'minigame', game: 'maze' }
    ];
    const miniGamePositions = [16, 33, 50, 67, 83, 94];
    const boardTasks = [...baseBoardTasks];
    miniGamePositions.slice().reverse().forEach((position, index) => {
        boardTasks.splice(position, 0, { ...miniGameTasks[miniGameTasks.length - 1 - index] });
    });
    boardTasks.forEach((task, index) => { task.num = index; });
 function createPlayers(count, names = []) {
   const livePlayers = Math.max(1, Math.min(3, Number(count) || 1));
   const profiles = [
     { name: names[0] || 'Гравець 1', avatar: selectedAvatar, accessory: selectedAccessory, isBot: false }
   ];
   if (livePlayers === 1) {
     profiles.push({ name: 'Робот Франкі', avatar: '🧟', isBot: true });
   } else {
     profiles.push({ name: names[1] || 'Гравець 2', avatar: '🧙', isBot: false });
     if (livePlayers === 3) profiles.push({ name: names[2] || 'Гравець 3', avatar: '🧛', isBot: false });
   }
   profiles.forEach((profile, index) => {
       const savedProfile = profileProgress.players[profile.name];
       if (savedProfile?.avatar) profile.avatar = savedProfile.avatar;
       if (profile.avatar === '🧸') profile.avatar = 'stitch';
       if (profile.avatar === '🟩') profile.avatar = 'creeper';
       else if (!profile.isBot && index > 0) profile.avatar = '🎃';
       if (!profile.accessories) profile.accessories = index === 0 ? { ...selectedAccessories } : {};
       if (profile.accessory) delete profile.accessory;
   });
   return profiles.map((profile, index) => ({
       id: index + 1,
       ...profile,
       pos: 0,
       score: 0,
       stars: profile.isBot ? 0 : getSavedPlayerStars(profile.name, index === 0 ? profileProgress.stars : 0),
       accessories: profile.isBot ? {} : { ...(profileProgress.players[profile.name]?.accessories || profile.accessories || {}) }
   }));
 }
 let players = createPlayers(1);
let currentPlayerIndex = 0;

function changePlayerCount(count) {
    const savedName = players[0] && players[0].name;
    players = createPlayers(count);
    if (savedName) players[0].name = savedName;
    resetGame();
    document.getElementById('gameStatus').innerText = `Гравців: ${players.length}. Ваш хід — киньте кубик`;
}

function selectSetupPlayerCount(count, button) {
    selectedSetupPlayerCount = Number(count);
    document.getElementById('setupError').textContent = '';
    document.querySelectorAll('.setup-count-btn').forEach(option => option.classList.remove('active'));
    button.classList.add('active');
    updateSetupPlayerFields();
}

function updateSetupPlayerFields() {
    document.getElementById('player2Field').classList.toggle('hidden', selectedSetupPlayerCount < 2);
    document.getElementById('player3Field').classList.toggle('hidden', selectedSetupPlayerCount < 3);
}

function startGame() {
    const input = document.getElementById('playerNameInput');
    const setupError = document.getElementById('setupError');
    const playerName = input.value.trim() || 'Гравець (Ви)';
    const names = [
        playerName,
        document.getElementById('playerName2Input').value.trim() || 'Гравець 2',
        document.getElementById('playerName3Input').value.trim() || 'Гравець 3'
    ];
    const enteredNames = names.slice(0, selectedSetupPlayerCount).map(name => name.toLocaleLowerCase().replace(/\s+/g, ' ').trim());
    const hasDuplicateNames = new Set(enteredNames).size !== enteredNames.length;
    if (hasDuplicateNames) {
        setupError.textContent = 'Введіть різні імена для кожного гравця.';
        return;
    }
    setupError.textContent = '';
    players = createPlayers(selectedSetupPlayerCount, names);
    document.getElementById('setupModal').classList.remove('active');
    resetGame();
    startHalloweenMusic();
    renderProfilePickers();
    document.getElementById('gameStatus').innerText = `${playerName}, ваш хід — киньте кубик`;
}

function restartGame() {
    players.forEach(player => {
        if (player.isBot) return;
        player.stars = 0;
        player.accessories = {};
        profileProgress.players[player.name] = { stars: 0, accessories: {} };
    });
    profileProgress.stars = 0;
    localStorage.setItem('englishBoardProfile', JSON.stringify(profileProgress));
    selectedSetupPlayerCount = players[1]?.isBot ? 1 : players.length;
    selectedAvatar = players[0]?.avatar || '🎃';
    selectedAccessories = {};
    closeProfileModal();
    const input = document.getElementById('playerNameInput');
    input.value = players[0]?.name || '';
    document.getElementById('playerName2Input').value = players[1]?.isBot ? '' : (players[1]?.name || '');
    document.getElementById('playerName3Input').value = players.length > 2 ? players[2].name : '';
    document.querySelectorAll('.setup-count-btn').forEach((button, index) => {
        button.classList.toggle('active', index + 1 === selectedSetupPlayerCount);
    });
    updateSetupPlayerFields();
    document.getElementById('questionModal').classList.remove('active');
    document.getElementById('setupModal').classList.add('active');
    setTimeout(() => input.focus(), 50);
}

function renderBoard() {
      const grid = document.getElementById('boardGrid');
    grid.innerHTML = '';
    const columns = getComputedStyle(grid).gridTemplateColumns.split(' ').length;
    const orderedTasks = [...boardTasks].sort((a, b) => a.num - b.num);
    orderedTasks.forEach((cell, index) => {
        const cellEl = document.createElement('div');
        const rowIndex = Math.floor(cell.num / columns);
        const visualColumn = rowIndex % 2 === 0 ? cell.num % columns : columns - 1 - (cell.num % columns);
        cellEl.style.gridColumn = String(visualColumn + 1);
        cellEl.style.gridRow = String(rowIndex + 1);
        cellEl.className = `cell${cell.num === boardTasks.length - 1 ? ' finish' : ''}${players.some(p => p.pos === cell.num) ? ' current' : ''}`;
        const numEl = document.createElement('div');
    numEl.className = 'cell-num';
    numEl.innerText = cell.num;
        const iconEl = document.createElement('div');
    iconEl.className = 'cell-icon';
    iconEl.innerText = cell.icon;
    iconEl.style.animationDelay = `${(cell.num % 7) * 0.12}s`;
        const titleEl = document.createElement('div');
    titleEl.className = 'cell-title';
    titleEl.innerText = cell.title;
        const tokensEl = document.createElement('div');
    tokensEl.className = 'player-tokens';
    players.forEach(p => {
    if (p.pos === cell.num) {
            const token = document.createElement('span');
        token.className = 'token';
        token.innerHTML = getAvatarMarkup(p, true);
        tokensEl.appendChild(token);
        }
        });
    if (index < orderedTasks.length - 1) {
        const row = Math.floor(cell.num / columns), nextRow = Math.floor((cell.num + 1) / columns);
        const col = row % 2 === 0 ? cell.num % columns : columns - 1 - (cell.num % columns);
        const nextCol = nextRow % 2 === 0 ? (cell.num + 1) % columns : columns - 1 - ((cell.num + 1) % columns);
        const arrowEl = document.createElement('span');
        arrowEl.className = nextRow === row ? `path-arrow ${nextCol > col ? 'arrow-right' : 'arrow-left'}` : 'path-arrow arrow-down';
        arrowEl.innerText = nextRow === row ? (nextCol > col ? '→' : '←') : '↓';
        arrowEl.setAttribute('aria-hidden', 'true');
        cellEl.appendChild(arrowEl);
    }
    cellEl.appendChild(numEl);
    cellEl.appendChild(iconEl);
    cellEl.appendChild(titleEl);
    cellEl.appendChild(tokensEl);
    grid.appendChild(cellEl);
      });
    renderScoreboard();
}

function renderScoreboard() {
      const sb = document.getElementById('scoreboard');
    sb.innerHTML = '';
    players.forEach((p, idx) => {
        const row = document.createElement('div');
    row.className = `player-row ${idx === currentPlayerIndex ? 'active' : ''} ${idx === currentPlayerIndex && p.isBot ? 'bot-active' : ''}`;
    const profileButton = p.isBot
        ? ''
        : `<button class="player-profile-btn" type="button" onclick="openProfileModal(${idx})" aria-label="Налаштувати профіль ${p.name}" title="Налаштувати профіль">⚙️</button>`;
    row.innerHTML = `<div class="player-info"><span class="player-avatar">${getAvatarMarkup(p)}</span><span>${p.name}${idx === currentPlayerIndex ? ' · хід' : ''}</span>${profileButton}</div><div class="player-score">⭐ ${p.score} б. · ${p.pos}/${boardTasks.length - 1}</div>`;
    sb.appendChild(row);
      });
}

function setBotTurnDisplay(isBotTurn) {
    const status = document.getElementById('gameStatus');
    const botImage = document.getElementById('botTurnImage');
    if (!status || !botImage) return;
    status.hidden = isBotTurn;
    botImage.hidden = !isBotTurn;
}

function showTurnReminder() {
    if (gameOver || players[currentPlayerIndex].isBot) return;
    const modal = document.getElementById('turnReminderModal');
    const title = document.getElementById('turnReminderTitle');
    const text = document.getElementById('turnReminderText');
    if (!modal) return;
    title.innerText = `Хід: ${players[currentPlayerIndex].name}`;
    text.innerText = 'Час кидати кубик і виконувати завдання.';
    modal.classList.add('active');
    setTimeout(() => document.querySelector('.turn-reminder-btn')?.focus(), 50);
}

function closeTurnReminder() {
    document.getElementById('turnReminderModal')?.classList.remove('active');
}

function showEffectModal(icon, title, text, action) {
    const modal = document.getElementById('effectModal');
    if (!modal) { action?.(); return; }
    document.getElementById('effectModalIcon').innerText = icon;
    document.getElementById('effectModalTitle').innerText = title;
    document.getElementById('effectModalText').innerText = text;
    pendingEffectAction = action;
    modal.classList.add('active');
    setTimeout(() => document.getElementById('effectModalBtn')?.focus(), 50);
    if (players[currentPlayerIndex]?.isBot) setTimeout(closeEffectModal, 1400);
}

function closeEffectModal() {
    document.getElementById('effectModal')?.classList.remove('active');
    const action = pendingEffectAction;
    pendingEffectAction = null;
    action?.();
}

function playMovePop() {
    if (!isMusicOn) return;
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    if (!musicContext) musicContext = new AudioContext();
    const now = musicContext.currentTime;
    const oscillator = musicContext.createOscillator();
    const gain = musicContext.createGain();
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(240, now);
    oscillator.frequency.exponentialRampToValueAtTime(520, now + 0.08);
    gain.gain.setValueAtTime(0.0001, now);
    gain.gain.exponentialRampToValueAtTime(0.08, now + 0.015);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.11);
    oscillator.connect(gain).connect(musicContext.destination);
    oscillator.start(now);
    oscillator.stop(now + 0.12);
}

let lastDiceRoll = 0;
let recentRolls = [];

function getRandomDiceRoll() {
    const getRandomInt = () => {
        if (window.crypto && window.crypto.getRandomValues) {
            const arr = new Uint32Array(1);
            window.crypto.getRandomValues(arr);
            return (arr[0] % 6) + 1;
        }
        return Math.floor(Math.random() * 6) + 1;
    };

    let roll = getRandomInt();

    // Запобігаємо випаданню однакових чисел поспіль між ходами гравців
    if (lastDiceRoll && roll === lastDiceRoll && Math.random() < 0.85) {
        roll = getRandomInt();
    }

    // Запобігаємо трикратному повторенню однакового числа
    if (recentRolls.length >= 2 && recentRolls[0] === roll && recentRolls[1] === roll) {
        roll = getRandomInt();
    }

    lastDiceRoll = roll;
    recentRolls.unshift(roll);
    if (recentRolls.length > 6) recentRolls.pop();

    return roll;
}

function playDiceRollSound() {
    if (!isMusicOn) return;
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    if (!musicContext) musicContext = new AudioContext();
    const now = musicContext.currentTime;
    for (let i = 0; i < 4; i++) {
        const time = now + i * 0.11;
        const osc = musicContext.createOscillator();
        const gain = musicContext.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(200 + Math.random() * 260, time);
        gain.gain.setValueAtTime(0.0001, time);
        gain.gain.exponentialRampToValueAtTime(0.05, time + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, time + 0.08);
        osc.connect(gain).connect(musicContext.destination);
        osc.start(time);
        osc.stop(time + 0.09);
    }
}

function animateDice(diceElement) {
    diceElement.classList.remove('dice-rolling');
    void diceElement.offsetWidth;
    diceElement.classList.add('dice-rolling');
    setTimeout(() => diceElement.classList.remove('dice-rolling'), 750);
}

function movePlayerStepByStep(player, steps, onComplete) {
    const totalSteps = Math.max(0, Number(steps) || 0);
    let completedSteps = 0;

    const moveNext = () => {
        if (completedSteps >= totalSteps) {
            onComplete?.();
            return;
        }

        player.pos = Math.min(boardTasks.length - 1, player.pos + 1);
        completedSteps += 1;
        renderBoard();
        playMovePop();
        setTimeout(moveNext, 280);
    };

    moveNext();
}

function executeDiceRoll(player, onFinish) {
    const diceDisplay = document.getElementById('diceDisplay');
    const rollBtn = document.getElementById('rollBtn');
    if (rollBtn) rollBtn.disabled = true;

    diceDisplay.classList.remove('dice-rolling');
    void diceDisplay.offsetWidth;
    diceDisplay.classList.add('dice-rolling');

    playDiceRollSound();

    const diceFaces = ['🎲', '⚀', '⚁', '⚂', '⚃', '⚄', '⚅'];
    const tumbleInterval = setInterval(() => {
        const temp = Math.floor(Math.random() * 6) + 1;
        diceDisplay.innerText = diceFaces[temp];
    }, 60);

    setTimeout(() => {
        clearInterval(tumbleInterval);
        diceDisplay.classList.remove('dice-rolling');
        const diceVal = getRandomDiceRoll();
        diceDisplay.innerText = diceFaces[diceVal];
        document.getElementById('gameStatus').innerText = `${player.name} викинув ${diceVal}.`;
        const steps = Math.min(diceVal, boardTasks.length - 1 - player.pos);
        movePlayerStepByStep(player, steps, () => {
            handleCellAction(player.pos);
            if (onFinish) onFinish(diceVal);
        });
    }, 650);
}

function rollDice() {
    if (gameOver || isResolvingTurn || players[currentPlayerIndex].isBot) return;
    isResolvingTurn = true;
    let player = players[currentPlayerIndex];
    executeDiceRoll(player);
}

function launchConfetti() {
    const layer = document.getElementById('confettiLayer');
    if (!layer) return;
    const colors = ['#ff7518', '#ffc247', '#63f2b5', '#67e8f9', '#c084fc', '#f9a8d4'];
    layer.innerHTML = Array.from({ length: 36 }, (_, index) => {
        const style = `--x:${Math.random() * 100}%;--drift:${(Math.random() * 180 - 90).toFixed(0)}px;--duration:${(1.8 + Math.random() * 1.8).toFixed(2)}s;--delay:${(Math.random() * .35).toFixed(2)}s;background:${colors[index % colors.length]}`;
        return `<span class="confetti-piece" style="${style}"></span>`;
    }).join('');
    setTimeout(() => { if (layer) layer.innerHTML = ''; }, 4200);
}

function finishMiniGame(success) {
    if (!activeMiniGame || activeMiniGame.finished) return;
    activeMiniGame.finished = true;
    const player = players[currentPlayerIndex];
    const feedback = document.getElementById('miniGameFeedback');
    if (success) {
        player.score += 20;
        if (!player.isBot) {
            player.stars = (Number(player.stars) || 0) + 20;
            saveProfileProgress(player);
        }
        feedback.textContent = '🎉 Чудово! Бонус: +20 балів і +20 зірочок!';
        feedback.className = 'mini-game-feedback correct';
        launchConfetti();
    } else {
        feedback.textContent = '🙂 Майже! Спробуй наступного разу.';
        feedback.className = 'mini-game-feedback wrong';
    }
    renderScoreboard();
    setTimeout(() => {
        if (activeMiniGame?.keyHandler) window.removeEventListener('keydown', activeMiniGame.keyHandler);
        document.getElementById('miniGameModal')?.classList.remove('active');
        activeMiniGame = null;
        nextTurn();
    }, 1100);
}

function showMiniGameModal(task) {
    const modal = document.getElementById('miniGameModal');
    const content = document.getElementById('miniGameContent');
    const description = document.getElementById('miniGameDescription');
    const feedback = document.getElementById('miniGameFeedback');
    const isBot = players[currentPlayerIndex].isBot;
    activeMiniGame = { task, finished: false };
    content.innerHTML = '';
    feedback.textContent = '';
    feedback.className = 'mini-game-feedback';
    modal.classList.add('active');

    if (task.game === 'match') {
        const pairs = [
            ['book', 'книга'], ['door', 'двері'], ['frog', 'жаба'], ['cake', 'торт']
        ];
        const cards = pairs.flatMap(([english, ukrainian], pair) => [
            { pair, text: english, language: 'en' }, { pair, text: ukrainian, language: 'uk' }
        ]).sort(() => Math.random() - 0.5);
        const memoryState = { open: [], matched: new Set(), locked: false };
        activeMiniGame.memoryState = memoryState;
        document.getElementById('miniGameTitle').innerText = 'Меморі';
        description.innerText = 'Відкривай по дві картки та знаходь пару: англійське слово + переклад.';
        content.innerHTML = `<div class="memory-grid">${cards.map((card, index) => `<button class="memory-card" type="button" data-card-index="${index}" ${isBot ? 'disabled' : ''}>❔</button>`).join('')}</div>`;
        const cardButtons = [...content.querySelectorAll('.memory-card')];
        const finishPairTurn = () => {
            const [first, second] = memoryState.open;
            first.button.textContent = first.card.text;
            second.button.textContent = second.card.text;
            first.button.classList.add('open'); second.button.classList.add('open');
            if (first.card.pair === second.card.pair) {
                first.button.classList.add('matched'); second.button.classList.add('matched');
                memoryState.matched.add(first.card.pair);
                memoryState.open = [];
                memoryState.locked = false;
                if (memoryState.matched.size === pairs.length) finishMiniGame(true);
            } else {
                first.button.classList.add('wrong'); second.button.classList.add('wrong');
                setTimeout(() => {
                    first.button.textContent = '❔'; second.button.textContent = '❔';
                    first.button.classList.remove('open', 'wrong'); second.button.classList.remove('open', 'wrong');
                    memoryState.open = []; memoryState.locked = false;
                }, 650);
            }
        };
        cardButtons.forEach((button, index) => {
            button.onclick = () => {
                if (memoryState.locked || memoryState.matched.has(cards[index].pair) || memoryState.open.some(item => item.index === index)) return;
                const entry = { index, card: cards[index], button };
                memoryState.open.push(entry);
                button.textContent = cards[index].text;
                button.classList.add('open');
                if (cards[index].language === 'en') speakWord(cards[index].text);
                else speakTask(cards[index].text);
                if (memoryState.open.length === 2) {
                    memoryState.locked = true;
                    setTimeout(finishPairTurn, 250);
                }
            };
        });
        if (isBot) setTimeout(() => finishMiniGame(true), 1600);
        return;
    }

    if (task.game === 'match') {
        const answer = 'book';
        const choices = ['книга', 'м’яч', 'вікно', 'жаба'];
        document.getElementById('miniGameTitle').innerText = 'Знайди пару';
        description.innerText = 'Який переклад відповідає слову book?';
        content.innerHTML = `<div class="mini-game-prompt">📗 <strong>book</strong></div><div class="mini-options">${choices.map(choice => `<button class="mini-option" type="button" ${isBot ? 'disabled' : ''}>${choice}</button>`).join('')}</div>`;
        content.querySelectorAll('.mini-option').forEach(button => {
            button.onclick = () => {
                const correct = button.textContent === translateAnswer(answer);
                button.classList.add(correct ? 'correct' : 'wrong');
                finishMiniGame(correct);
            };
        });
        if (isBot) setTimeout(() => finishMiniGame(true), 1000);
        return;
    }

    if (task.game === 'scramble') {
        const answer = 'pencil';
        const letters = answer.split('').sort((a, b) => (a < b ? 1 : -1));
        let selected = '';
        document.getElementById('miniGameTitle').innerText = 'Збери слово';
        description.innerText = 'Склади англійське слово з літер.';
        content.innerHTML = `<div class="mini-game-prompt">✏️ Правильне слово має означати «олівець»</div><div class="scramble-word" id="scrambleWord">_ _ _ _ _ _</div><div class="scramble-letters">${letters.map((letter, index) => `<button class="mini-letter" type="button" data-letter="${letter}" ${isBot ? 'disabled' : ''}>${letter}</button>`).join('')}</div>`;
        const wordView = document.getElementById('scrambleWord');
        content.querySelectorAll('.mini-letter').forEach(button => {
            button.onclick = () => {
                selected += button.dataset.letter;
                button.classList.add('used');
                wordView.textContent = selected.split('').join(' ');
                if (selected.length === answer.length) finishMiniGame(selected === answer);
            };
        });
        if (isBot) setTimeout(() => finishMiniGame(true), 1300);
        return;
    }

    const maze = [
        [0,0,1,1,1,0,0,0,0], [1,0,1,0,0,0,1,1,0], [0,0,0,0,1,0,0,1,0],
        [0,1,1,0,1,0,1,1,0], [0,0,0,0,0,0,1,0,0], [1,1,1,1,1,0,1,0,1],
        [0,0,0,0,0,0,0,0,1], [0,1,1,1,1,1,1,0,1], [0,0,0,0,0,0,0,0,0]
    ];
    const position = { row: 0, col: 0 };
    const renderMaze = () => {
        const board = document.getElementById('mazeBoard');
        const goalRow = maze.length - 1, goalCol = maze[0].length - 1;
        board.innerHTML = maze.map((row, r) => row.map((wall, c) => `<div class="maze-cell ${wall ? 'wall' : ''} ${r === goalRow && c === goalCol ? 'goal' : ''}">${wall ? '' : (r === position.row && c === position.col ? '🧙' : r === goalRow && c === goalCol ? '🏁' : '')}</div>`).join('')).join('');
    };
    const move = (dr, dc) => {
        if (activeMiniGame?.finished || isBot) return;
        const nextRow = position.row + dr, nextCol = position.col + dc;
        if (nextRow < 0 || nextRow >= maze.length || nextCol < 0 || nextCol >= maze[0].length || maze[nextRow][nextCol]) return;
        position.row = nextRow; position.col = nextCol; renderMaze();
        if (position.row === maze.length - 1 && position.col === maze[0].length - 1) finishMiniGame(true);
    };
    document.getElementById('miniGameTitle').innerText = 'Мінілабіринт';
    description.innerText = 'Проведи героя до прапорця стрілками або кнопками.';
    content.innerHTML = `<div class="maze-board" id="mazeBoard"></div><div class="maze-controls"><button class="maze-control up" type="button" ${isBot ? 'disabled' : ''}>⬆️</button><button class="maze-control left" type="button" ${isBot ? 'disabled' : ''}>⬅️</button><button class="maze-control down" type="button" ${isBot ? 'disabled' : ''}>⬇️</button><button class="maze-control right" type="button" ${isBot ? 'disabled' : ''}>➡️</button></div>`;
    renderMaze();
    const controls = content.querySelectorAll('.maze-control');
    const stopScroll = handler => event => { event.preventDefault(); handler(); };
    controls[0].onclick = stopScroll(() => move(-1, 0)); controls[1].onclick = stopScroll(() => move(0, -1));
    controls[2].onclick = stopScroll(() => move(1, 0)); controls[3].onclick = stopScroll(() => move(0, 1));
    let touchStart = null;
    const mazeBoard = document.getElementById('mazeBoard');
    mazeBoard.addEventListener('touchstart', event => {
        const touch = event.changedTouches[0];
        touchStart = { x: touch.clientX, y: touch.clientY };
    }, { passive: true });
    mazeBoard.addEventListener('touchend', event => {
        event.preventDefault();
        if (!touchStart) return;
        const touch = event.changedTouches[0];
        const dx = touch.clientX - touchStart.x, dy = touch.clientY - touchStart.y;
        touchStart = null;
        if (Math.max(Math.abs(dx), Math.abs(dy)) < 18) return;
        if (Math.abs(dx) > Math.abs(dy)) move(0, dx > 0 ? 1 : -1);
        else move(dy > 0 ? 1 : -1, 0);
    }, { passive: true });
    activeMiniGame.keyHandler = event => {
        const direction = { ArrowUp: [-1, 0], ArrowLeft: [0, -1], ArrowDown: [1, 0], ArrowRight: [0, 1] }[event.key];
        if (!direction) return;
        event.preventDefault();
        move(...direction);
    };
    window.addEventListener('keydown', activeMiniGame.keyHandler);
    if (isBot) setTimeout(() => finishMiniGame(true), 1500);
}

function handleCellAction(cellNum) {
    const miniGameByCell = {
        16: 'match', 33: 'scramble', 50: 'maze',
        67: 'match', 83: 'scramble', 94: 'maze'
    };
    const task = miniGameByCell[cellNum]
        ? { num: cellNum, title: 'Mini game', icon: '🧩', type: 'minigame', game: miniGameByCell[cellNum] }
        : boardTasks[cellNum];
    currentTask = task;
    const player = players[currentPlayerIndex];
    if (task.type === 'minigame') {
        showMiniGameModal(task);
        return;
    }
    if (task.type === 'forward') {
        showEffectModal('🚀', 'Бонусний хід!', `${player.name} переміщується на ${task.val} клітинки вперед.`, () => {
            player.pos = Math.min(boardTasks.length - 1, player.pos + task.val);
            renderBoard();
            nextTurn();
        });
        return;
    }
    if (task.type === 'back') {
        showEffectModal('🌀', 'Ой, назад!', `${player.name} повертається на ${task.val} клітинки назад.`, () => {
            player.pos = Math.max(0, player.pos - task.val);
            renderBoard();
            nextTurn();
        });
        return;
    }
    if (task.num === boardTasks.length - 1) {
        gameOver = true;
        document.getElementById('gameStatus').innerText = `🏆 ${players[currentPlayerIndex].name} переміг!`;
        document.getElementById('rollBtn').disabled = true;
        showEffectModal('🏆', 'Перемога!', `${player.name} дійшов до фінішу!`, () => {});
        return;
    }
    if (task.type === 'miss') {
        showEffectModal('⏸️', 'Пропуск ходу', `${player.name} пропускає наступний хід.`, () => {
            document.getElementById('gameStatus').innerText = `${player.name} пропускає наступний хід.`;
            nextTurn();
        });
        return;
    }
    if (task.prompt) { showQuestionModal(task); } else { nextTurn(); }
}

function createTaskIllustration(task) {
    const palettes = [
        ['#ff7518', '#ffc247'], ['#7c3aed', '#c084fc'], ['#0ea5e9', '#67e8f9'],
        ['#10b981', '#a7f3d0'], ['#ec4899', '#f9a8d4'], ['#f59e0b', '#fde68a']
    ];
    const palette = palettes[(task.num || 0) % palettes.length];
    const taskIconOverrides = { doll: '🪆', rollerblades: '🛼', fast: '💨', open: '🚪🔓' };
    const matchingVocabularyItem = vocabulary.find(([word]) => word === task.answer);
    const icon = String(taskIconOverrides[task.answer] || matchingVocabularyItem?.[1] || task.icon || '✨');
    const safeIcon = icon.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 180" role="img" aria-label="Ілюстрація до завдання">
        <defs>
            <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop stop-color="${palette[0]}"/><stop offset="1" stop-color="${palette[1]}"/></linearGradient>
            <filter id="shadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="0" dy="7" stdDeviation="6" flood-color="#160b2b" flood-opacity=".35"/></filter>
        </defs>
        <rect x="5" y="5" width="350" height="170" rx="28" fill="url(#bg)"/>
        <circle cx="44" cy="36" r="18" fill="#fff" opacity=".2"/><circle cx="320" cy="145" r="32" fill="#fff" opacity=".16"/>
        <path d="M24 137c48-31 85 19 133-7s89-35 179 8" fill="none" stroke="#fff" stroke-width="5" opacity=".22" stroke-linecap="round"/>
        <text x="180" y="119" text-anchor="middle" font-size="86" font-family="Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji,sans-serif" filter="url(#shadow)">${safeIcon}</text>
    </svg>`;
    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function showQuestionModal(task) {
      const modal = document.getElementById('questionModal');
    const currentPlayer = players[currentPlayerIndex];
    const isBotTurn = currentPlayer.isBot;
    const modalHeader = document.getElementById('modalHeader');
    const botTaskImage = document.getElementById('botTaskImage');
    modalHeader.innerText = currentPlayer.isBot ? `🤖 ХІД: ${currentPlayer.name}` : `КЛІТИНКА ${task.num}`;
    modalHeader.hidden = currentPlayer.isBot;
    if (botTaskImage) botTaskImage.hidden = !currentPlayer.isBot;
    document.getElementById('modalPrompt').children[0].innerText = task.prompt;
    const modalImg = document.getElementById('modalImg');
    // Показуємо живу emoji-анімацію замість статичної картинки.
    const taskVideo = task.video || '';
    modalImg.className = `modal-img${taskVideo ? ' has-task-image has-task-video' : ' has-task-image'}`;
    if (taskVideo) {
        modalImg.innerHTML = `<video src="${taskVideo}" autoplay loop muted playsinline aria-label="${task.title}"></video>`;
    } else {
        modalImg.innerHTML = `<img src="${createTaskIllustration(task)}" alt="Ілюстрація до завдання" loading="eager">`;
        modalImg.classList.add('has-task-image');
    }
    void modalImg.offsetWidth;
      const optionsGrid = document.getElementById('optionsGrid');
    const answerInput = document.getElementById('answerInput');
    optionsGrid.innerHTML = '';
    selectedOptionIndex = null;
    answerSubmitted = false;
    answerInput.value = '';
    answerInput.classList.remove('correct', 'wrong');
    answerInput.hidden = !task.requiresTyping;
    optionsGrid.hidden = task.requiresTyping;
    document.getElementById('submitBtn').innerText = 'ПЕРЕВІРИТИ';
    modal.classList.toggle('bot-turn-modal', isBotTurn);
    answerInput.disabled = isBotTurn;
    document.getElementById('submitBtn').disabled = isBotTurn;
    const feedback = document.getElementById('answerFeedback');
    feedback.textContent = '';
    feedback.className = 'answer-feedback';
    speakTask(task.prompt);
    answerInput.oninput = () => {
        document.getElementById('submitBtn').disabled = !answerInput.value.trim();
    };
    answerInput.onkeydown = (event) => {
        if (event.key === 'Enter' && answerInput.value.trim()) confirmAnswer();
    };
    task.options.forEach((opt, idx) => {
        const btn = document.createElement('button');
    btn.className = 'btn-option';
    btn.disabled = isBotTurn;
    btn.innerHTML = `<span class="option-text"><strong>${opt}</strong><small class="option-translation">${translateAnswer(opt)}</small></span><span aria-hidden="true">🔊</span>`;
    btn.onclick = () => selectOption(idx, opt);
    optionsGrid.appendChild(btn);
      });
    modal.classList.add('active');
    if (players[currentPlayerIndex].isBot) {
        document.getElementById('gameStatus').innerText = `🤖 ${currentPlayer.name} слухає завдання…`;
        document.getElementById('gameStatus').classList.add('bot-status');
        const listeningAndThinkingTime = Math.max(4200, task.prompt.length * 75);
        setTimeout(() => {
        document.getElementById('gameStatus').innerText = `🤖 ${currentPlayer.name} обирає відповідь…`;
          if (task.requiresTyping) {
            answerInput.value = task.answer;
          } else {
            const randomOpt = Math.floor(Math.random() * task.options.length);
            selectOption(randomOpt, task.options[randomOpt]);
          }
        setTimeout(() => confirmAnswer(), 1600);
        }, listeningAndThinkingTime);
    }
}

function playCurrentPromptSound() { if (currentTask) speakTask(currentTask.prompt); }
function selectOption(index, word) {
    selectedOptionIndex = index;
    speakWord(word);
    document.querySelectorAll('.btn-option').forEach((btn, idx) => { btn.classList.toggle('selected', idx === index); });
    document.getElementById('submitBtn').disabled = false;
}

function confirmAnswer() {
    if (!currentTask || !isResolvingTurn) return;

    if (answerSubmitted) {
        document.getElementById('questionModal').classList.remove('active');
        answerSubmitted = false;
        nextTurn();
        return;
    }

    const answerInput = document.getElementById('answerInput');
    if (currentTask.requiresTyping && !answerInput.value.trim()) return;
    if (!currentTask.requiresTyping && selectedOptionIndex === null) return;
      const btns = document.querySelectorAll('.btn-option');
      const typedAnswer = answerInput.value.trim().toLowerCase().replace(/\s+/g, ' ');
      const isCorrect = currentTask.requiresTyping
        ? typedAnswer === currentTask.answer.toLowerCase()
        : currentTask.options[selectedOptionIndex] === currentTask.answer;
    if (currentTask.requiresTyping) {
        answerInput.classList.add(isCorrect ? 'correct' : 'wrong');
    } else {
        btns[selectedOptionIndex].classList.add(isCorrect ? 'correct' : 'wrong');
    }
    answerSubmitted = true;
    const feedback = document.getElementById('answerFeedback');
    feedback.textContent = isCorrect
        ? '🎉 Правильно! Чудова відповідь!'
        : `🙂 Майже! Правильна відповідь: ${currentTask.answer}`;
    feedback.className = `answer-feedback ${isCorrect ? 'correct' : 'wrong'}`;
    speakAnswerFeedback(isCorrect, currentTask.answer);
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerText = 'ПРОДОВЖИТИ';
    submitBtn.disabled = false;
    if (isCorrect) {
        players[currentPlayerIndex].score += 10;
        if (!players[currentPlayerIndex].isBot) {
            players[currentPlayerIndex].stars = (Number(players[currentPlayerIndex].stars) || 0) + 10;
            saveProfileProgress(players[currentPlayerIndex]);
            renderProfilePickers();
        }
        launchConfetti();
    }
    document.getElementById('gameStatus').innerText = isCorrect ? 'Правильно! +10 балів' : `Правильна відповідь: ${currentTask.answer}`;
    if (players[currentPlayerIndex].isBot) {
        setTimeout(() => {
            if (answerSubmitted && isResolvingTurn) confirmAnswer();
        }, 1300);
    }
}

function nextTurn() {
    if (gameOver) return;
    currentPlayerIndex = (currentPlayerIndex + 1) % players.length;
    renderBoard();
    document.getElementById('rollBtn').disabled = false;
    isResolvingTurn = false;
    const botTurn = players[currentPlayerIndex].isBot;
    setBotTurnDisplay(botTurn);
    document.getElementById('gameStatus').innerText = botTurn ? `🤖 Хід робота: ${players[currentPlayerIndex].name}` : 'Ваш хід — киньте кубик';
    document.getElementById('gameStatus').classList.toggle('bot-status', botTurn);
    document.getElementById('scoreboard').classList.toggle('bot-turn', botTurn);
    if (!botTurn) showTurnReminder();
    if (botTurn) {
        const botIndex = currentPlayerIndex;
        const scoreboard = document.getElementById('scoreboard');
        if (window.matchMedia('(max-width: 759px)').matches) {
            setTimeout(() => scoreboard.scrollIntoView({ behavior: 'smooth', block: 'center' }), 50);
        }
        setTimeout(() => {
            if (gameOver || currentPlayerIndex !== botIndex || !players[botIndex].isBot) return;
            isResolvingTurn = false;
            renderScoreboard();
            rollDiceForBot();
        }, 900);
    }
}

function rollDiceForBot() {
    if (gameOver || isResolvingTurn || !players[currentPlayerIndex].isBot) return;
    isResolvingTurn = true;
    const player = players[currentPlayerIndex];
    setBotTurnDisplay(false);
    executeDiceRoll(player);
}

function resetGame() {
    if (activeMiniGame?.keyHandler) window.removeEventListener('keydown', activeMiniGame.keyHandler);
    activeMiniGame = null;
    document.getElementById('miniGameModal')?.classList.remove('active');
    players.forEach(player => { player.pos = 0; player.score = 0; });
    currentPlayerIndex = 0;
    currentTask = null;
    selectedOptionIndex = null;
    answerSubmitted = false;
    gameOver = false;
    isResolvingTurn = false;
    lastDiceRoll = 0;
    recentRolls = [];
    document.getElementById('questionModal').classList.remove('active');
    document.getElementById('effectModal')?.classList.remove('active');
    pendingEffectAction = null;
    setBotTurnDisplay(false);
    document.getElementById('diceDisplay').innerText = '🎲';
    document.getElementById('rollBtn').disabled = false;
    document.getElementById('gameStatus').innerText = 'Ваш хід — киньте кубик';
    renderBoard();
}

renderBoard();
renderProfilePickers();
document.getElementById('playerNameInput').focus();
  
