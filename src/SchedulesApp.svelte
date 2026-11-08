<script>
  import { onMount } from 'svelte';

  let clockTime = '';
  let apiDoctors = [];
  let activeDept = 'ALL';
  let activeSession = 'ALL';
  let searchQuery = '';

  const baseTemplates = [
    {
      dept: 'General Medical',
      tagClass: 'tag-general',
      dotColor: '#16a34a',
      regPage: 'regis-general.php',
      service: 'General Medical Consultation',
      defaultDoc: 'Dr. Roberto Santos, MD',
      days: [1, 2, 3, 4, 5, 6],
      startTime: '08:00',
      endTime: '10:00',
      session: 'AM',
      totalSlots: 15
    },
    {
      dept: 'General Medical',
      tagClass: 'tag-general',
      dotColor: '#16a34a',
      regPage: 'regis-general.php',
      service: 'Physical Examination & Preventive Healthcare',
      defaultDoc: 'Dr. Maria Elena Cruz, MD',
      days: [1, 2, 3, 4, 5],
      startTime: '10:00',
      endTime: '12:00',
      session: 'AM',
      totalSlots: 15
    },
    {
      dept: 'General Medical',
      tagClass: 'tag-general',
      dotColor: '#16a34a',
      regPage: 'regis-general.php',
      service: 'Chronic Disease Management & Lab Requests',
      defaultDoc: 'Dr. Roberto Santos, MD',
      days: [1, 3, 5],
      startTime: '13:00',
      endTime: '16:00',
      session: 'PM',
      totalSlots: 12
    },
    {
      dept: 'Neurology',
      tagClass: 'tag-neuro',
      dotColor: '#0284c7',
      regPage: 'regis-neurology.php',
      service: 'Neurology Consultation & Headache Management',
      defaultDoc: 'Dr. Gabriel Villanueva, MD, FPNA',
      days: [1, 2, 4, 5],
      startTime: '08:30',
      endTime: '11:30',
      session: 'AM',
      totalSlots: 10
    },
    {
      dept: 'Neurology',
      tagClass: 'tag-neuro',
      dotColor: '#0284c7',
      regPage: 'regis-neurology.php',
      service: 'Stroke, Epilepsy & Neurological Diagnostic Testing',
      defaultDoc: 'Dr. Gabriel Villanueva, MD, FPNA',
      days: [2, 3, 4, 6],
      startTime: '13:30',
      endTime: '16:30',
      session: 'PM',
      totalSlots: 10
    },
    {
      dept: 'OPU Psychiatry',
      tagClass: 'tag-psych',
      dotColor: '#7c3aed',
      regPage: 'regis-psychiatry.php',
      service: 'Psychotherapy & Psychological Assessment',
      defaultDoc: 'Dr. Angela Reyes, MD, FPAFP',
      days: [1, 2, 3, 4, 5],
      startTime: '08:00',
      endTime: '11:30',
      session: 'AM',
      totalSlots: 14
    },
    {
      dept: 'OPU Psychiatry',
      tagClass: 'tag-psych',
      dotColor: '#7c3aed',
      regPage: 'regis-psychiatry.php',
      service: 'Child & Adolescent Psychology / Rehabilitation',
      defaultDoc: 'Dr. Marco Mendoza, MD',
      days: [1, 2, 3, 4, 5, 6],
      startTime: '13:00',
      endTime: '17:00',
      session: 'PM',
      totalSlots: 14
    }
  ];

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  function getNextClinicDate(fromDate) {
    const d = new Date(fromDate);
    d.setDate(d.getDate() + 1);
    if (d.getDay() === 0) d.setDate(d.getDate() + 1);
    return d;
  }

  let selectedDate = getNextClinicDate(today);
  let currentMonth = selectedDate.getMonth();
  let currentYear = selectedDate.getFullYear();

  function toYMD(dateObj) {
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function formatTime12(time24) {
    if (!time24) return '';
    const [hStr, mStr] = time24.split(':');
    let h = parseInt(hStr, 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${mStr} ${ampm}`;
  }

  function normalizeDeptName(rawDept) {
    const lower = (rawDept || '').toLowerCase();
    if (lower.includes('psych') || lower.includes('opu')) return 'OPU Psychiatry';
    if (lower.includes('neuro')) return 'Neurology';
    return 'General Medical';
  }

  onMount(() => {
    const updateClock = () => {
      clockTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    };
    updateClock();
    const timer = setInterval(updateClock, 1000);

    fetch('/sdn/api/schedule.php')
      .then(r => r.json())
      .then(res => {
        if (res && res.success && Array.isArray(res.data)) {
          apiDoctors = res.data;
        }
      })
      .catch(() => {});

    return () => clearInterval(timer);
  });
</script>

<main class="sched-svelte-placeholder">
  <!-- Rendered directly by pages/schedules.php -->
</main>
