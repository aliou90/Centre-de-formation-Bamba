// Auto positionnement du texte dans le champ de recherche
function adjustSearchDirection(input) {
    const text = input.value.trim();
    // Détection très simple : si le texte commence par un caractère arabe, on passe en RTL
    const isArabic = /^[\u0600-\u06FF]/.test(text);
    input.style.direction = isArabic ? 'rtl' : 'ltr';
    input.style.textAlign = isArabic ? 'right' : 'left';
}