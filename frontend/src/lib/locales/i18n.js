import { tick } from 'svelte';
import { resizeHeading } from '../h1.js';
import { writable } from 'svelte/store';
import en from './en.json';
import ru from './ru.json';

const dictionaries = { en, ru };

const savedLang = localStorage.getItem('lang') || 'en';

document.documentElement.lang = savedLang;

const currentLang = writable(savedLang);
const dict = writable(dictionaries[savedLang]);

async function setLang(lang) {
  localStorage.setItem('lang', lang);
  currentLang.set(lang);
  dict.set(dictionaries[lang]);

  document.documentElement.lang = lang;

  await tick();

  const headings = document.querySelectorAll('main h1');
  headings.forEach(h1 => resizeHeading(h1));
}

export { currentLang, dict, setLang };
