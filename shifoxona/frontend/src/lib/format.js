export const roles = { admin: 'Administrator', director: 'Shifoxona rahbari', doctor: 'Shifokor' };
export const statuses = { waiting: 'Kutilmoqda', in_progress: 'Qabulda', completed: 'Yakunlangan' };
export function fullName(patient) {
  return patient.last_name + ' ' + patient.first_name;
}

export function initials(name) {
  if (!name) {
    return 'SH';
  }

  const words = name.trim().split(/\s+/);
  let result = '';
  for (let i = 0; i < words.length && i < 2; i++) {
    result += words[i][0];
  }
  return result || 'SH';
}
const months = [
  'yanvar',
  'fevral',
  'mart',
  'aprel',
  'may',
  'iyun',
  'iyul',
  'avgust',
  'sentabr',
  'oktabr',
  'noyabr',
  'dekabr',
];
function dateParts(value) {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Tashkent',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(new Date(value));

  const result = {};
  for (const part of parts) {
    result[part.type] = part.value;
  }
  return result;
}
export function dateTime(value, onlyDate = false) {
  if (!value) {
    return '—';
  }
  const p = dateParts(value);
  const month = months[Number(p.month) - 1].slice(0, 3);
  let text = `${p.day} ${month} ${p.year}`;
  if (!onlyDate) {
    text += `, ${p.hour}:${p.minute}`;
  }
  return text;
}
export function fullDate(value = new Date()) {
  const p = dateParts(value);
  return `${Number(p.day)}-${months[Number(p.month) - 1]} ${p.year}`;
}
export function weekday(value) {
  return ['Yak', 'Du', 'Se', 'Chor', 'Pay', 'Ju', 'Sha'][
    new Date(`${value}T12:00:00+05:00`).getUTCDay()
  ];
}
export function inputDate(value = new Date()) {
  const p = dateParts(value);
  return `${p.year}-${p.month}-${p.day}T${p.hour}:${p.minute}`;
}
