<?php
// Fixed list of game types for the manual-entry dropdown. Stored into the
// same `categories` column BGG-sourced games use, so they render
// consistently as tags on the detail page either way.
const GAME_TYPES = [
    'Strategy', 'Family', 'Party', 'Cooperative', 'Card Game',
    'Dice Game', 'Puzzle', 'Wargame', "Children's Game", 'Abstract', 'Other',
];
