import dayjs from 'dayjs';

export function formatDisplayDate(value) {
  if (!value) return '';
  const datePart = String(value).slice(0, 10);
  const d = dayjs(datePart);
  return d.isValid() ? d.format('DD-MMM-YYYY') : String(value);
}

export function formatDisplayDateTime(value) {
  if (!value) return '';
  const d = dayjs(value);
  return d.isValid() ? d.format('DD-MMM-YYYY HH:mm') : String(value);
}
