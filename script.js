const API_URL = 'api.php';
const USER_ROLE = document.body?.dataset.role || 'public';
const CAN_EDIT = USER_ROLE === 'pegawai';
const CAN_CREATE_ENTITIES = USER_ROLE === 'pegawai'
  ? ['balita', 'ibu', 'imunisasi', 'pertumbuhan', 'jadwal']
  : (USER_ROLE === 'user' || USER_ROLE === 'warga')
    ? ['balita', 'ibu', 'imunisasi', 'pertumbuhan']
    : [];

const ENTITY_CONFIG = {
  balita: {
    pk: 'id_balita',
    tbodyId: 'tbodyBalita',
    formId: 'balitaForm',
    columns: ['nama', 'tanggal_lahir', 'jenis_kelamin', 'nama_ibu'],
    labels: {
      nama: 'Nama',
      tanggal_lahir: 'Tanggal Lahir',
      jenis_kelamin: 'Jenis Kelamin (L/P)',
      nama_ibu: 'Nama Ibu'
    },
    fromForm: (f) => ({
      nama: f.nama.value.trim(),
      tanggal_lahir: f.tanggal.value,
      jenis_kelamin: f.jk.value,
      nama_ibu: f.nama_ibu.value.trim()
    })
  },
  ibu: {
    pk: 'id_ibu',
    tbodyId: 'tbodyIbu',
    formId: 'ibuForm',
    columns: ['nama', 'usia_kehamilan_minggu', 'alamat'],
    labels: {
      nama: 'Nama',
      usia_kehamilan_minggu: 'Usia Kehamilan (minggu)',
      alamat: 'Alamat'
    },
    fromForm: (f) => ({
      nama: f.nama.value.trim(),
      usia_kehamilan_minggu: f.usia.value,
      alamat: f.alamat.value.trim()
    })
  },
  imunisasi: {
    pk: 'id_imunisasi',
    tbodyId: 'tbodyImun',
    formId: 'imunForm',
    columns: ['nama_balita', 'jenis_imunisasi', 'tanggal'],
    labels: {
      nama_balita: 'Nama Balita',
      jenis_imunisasi: 'Jenis Imunisasi',
      tanggal: 'Tanggal'
    },
    fromForm: (f) => ({
      nama_balita: f.nama.value.trim(),
      jenis_imunisasi: f.jenis.value.trim(),
      tanggal: f.tanggal.value
    })
  },
  pertumbuhan: {
    pk: 'id_pertumbuhan',
    tbodyId: 'tbodyGrowth',
    formId: 'growthForm',
    columns: ['nama_balita', 'berat_kg', 'tinggi_cm', 'tanggal'],
    labels: {
      nama_balita: 'Nama Balita',
      berat_kg: 'Berat (kg)',
      tinggi_cm: 'Tinggi (cm)',
      tanggal: 'Tanggal'
    },
    fromForm: (f) => ({
      nama_balita: f.nama.value.trim(),
      berat_kg: f.berat.value,
      tinggi_cm: f.tinggi.value,
      tanggal: f.tanggal.value
    })
  },
  jadwal: {
    pk: 'id_jadwal',
    tbodyId: 'tbodyJadwal',
    formId: 'jadwalForm',
    columns: ['tanggal', 'tempat', 'keterangan'],
    labels: {
      tanggal: 'Tanggal',
      tempat: 'Tempat',
      keterangan: 'Keterangan'
    },
    fromForm: (f) => ({
      tanggal: f.tanggal.value,
      tempat: f.tempat.value.trim(),
      keterangan: f.keterangan.value.trim()
    })
  }
};

const fmtDate = (value) => {
  if (!value) return '';
  const dt = new Date(value);
  if (Number.isNaN(dt.getTime())) return value;
  return dt.toLocaleDateString('id-ID');
};

async function apiRequest(entity, action, method = 'GET', payload = null) {
  const url = `${API_URL}?entity=${encodeURIComponent(entity)}&action=${encodeURIComponent(action)}`;
  const options = { method, headers: {} };

  if (payload !== null) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(payload);
  }

  const response = await fetch(url, options);
  const json = await response.json();

  if (!response.ok || !json.ok) {
    throw new Error(json.message || 'Permintaan API gagal');
  }

  return json;
}

async function fetchList(entity) {
  const result = await apiRequest(entity, 'list');
  return result.data || [];
}

function toMonthAge(birthDate, measureDate) {
  if (!birthDate || !measureDate) return null;
  const b = new Date(birthDate);
  const m = new Date(measureDate);
  if (Number.isNaN(b.getTime()) || Number.isNaN(m.getTime()) || m < b) return null;

  let months = (m.getFullYear() - b.getFullYear()) * 12 + (m.getMonth() - b.getMonth());
  if (m.getDate() < b.getDate()) months -= 1;
  return Math.max(0, months);
}

function interpolateRange(ageMonths, points) {
  if (ageMonths === null || ageMonths < points[0].age || ageMonths > points[points.length - 1].age) return null;
  for (let i = 0; i < points.length - 1; i += 1) {
    const a = points[i];
    const b = points[i + 1];
    if (ageMonths === a.age) return { min: a.min, max: a.max };
    if (ageMonths === b.age) return { min: b.min, max: b.max };
    if (ageMonths > a.age && ageMonths < b.age) {
      const t = (ageMonths - a.age) / (b.age - a.age);
      return {
        min: a.min + (b.min - a.min) * t,
        max: a.max + (b.max - a.max) * t
      };
    }
  }
  return null;
}

function getWeightRange(ageMonths, sex) {
  const male = [
    { age: 9, min: 7.1, max: 9.9 },
    { age: 12, min: 8.6, max: 12.2 },
    { age: 24, min: 10.3, max: 15.3 },
    { age: 36, min: 11.3, max: 17.4 },
    { age: 48, min: 12.6, max: 20.2 },
    { age: 60, min: 14.1, max: 21.0 }
  ];
  const female = [
    { age: 9, min: 6.5, max: 9.3 },
    { age: 12, min: 7.9, max: 11.5 },
    { age: 24, min: 9.7, max: 14.8 },
    { age: 36, min: 10.8, max: 17.1 },
    { age: 48, min: 12.1, max: 20.3 },
    { age: 60, min: 13.7, max: 21.2 }
  ];
  return interpolateRange(ageMonths, String(sex).toUpperCase() === 'L' ? male : female);
}

function getHeightRange(ageMonths) {
  const points = [
    { age: 9, min: 65.3, max: 77.4 },
    { age: 12, min: 68.9, max: 81.7 },
    { age: 24, min: 79.8, max: 92.6 },
    { age: 60, min: 100.7, max: 117.1 }
  ];
  return interpolateRange(ageMonths, points);
}

function classifyByRange(value, range) {
  if (!range) return { text: 'Acuan tidak tersedia', cls: 'warn', ok: null };
  if (Number(value) >= range.min && Number(value) <= range.max) return { text: 'Normal', cls: 'ok', ok: true };
  return { text: 'Tidak Normal', cls: 'bad', ok: false };
}

function buildGrowthFeedback(weightStatus, heightStatus, weightVal, heightVal, weightRange, heightRange) {
  if (weightStatus.ok === true && heightStatus.ok === true) {
    return 'Normal: pertahankan asupan gizi, tidur cukup, dan stimulasi aktif agar tumbuh kembang tetap optimal.';
  }

  const notes = [];
  if (weightRange) {
    if (Number(weightVal) < weightRange.min) notes.push('BB di bawah acuan, tingkatkan protein dan jadwal makan.');
    if (Number(weightVal) > weightRange.max) notes.push('BB di atas acuan, kurangi gula/camilan tinggi kalori.');
  }
  if (heightRange) {
    if (Number(heightVal) < heightRange.min) notes.push('TB di bawah acuan, optimalkan tidur dan asupan protein-kalsium.');
    if (Number(heightVal) > heightRange.max) notes.push('TB di atas acuan, tetap pantau agar proporsional.');
  }

  return notes.length ? notes.join(' ') : 'Lengkapi data balita (tanggal lahir/jenis kelamin) agar evaluasi lebih akurat.';
}

function createStatusBadge(status) {
  const span = document.createElement('span');
  span.className = `status-badge ${status.cls}`;
  span.textContent = status.text;
  return span;
}

async function renderGrowthChart(rows) {
  const canvas = document.getElementById('growthChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const w = canvas.width;
  const h = canvas.height;
  ctx.clearRect(0, 0, w, h);

  const pad = { top: 20, right: 48, bottom: 52, left: 48 };
  const plotW = w - pad.left - pad.right;
  const plotH = h - pad.top - pad.bottom;

  ctx.strokeStyle = '#d3e5f3';
  ctx.beginPath();
  ctx.moveTo(pad.left, pad.top);
  ctx.lineTo(pad.left, h - pad.bottom);
  ctx.lineTo(w - pad.right, h - pad.bottom);
  ctx.stroke();

  if (!rows.length) {
    ctx.fillStyle = '#5d6b7a';
    ctx.font = '14px Poppins, sans-serif';
    ctx.fillText('Grafik akan tampil setelah data pertumbuhan ditambahkan', 64, h / 2);
    return;
  }

  const maxW = Math.max(...rows.map((r) => Number(r.berat_kg) || 0), 1);
  const maxH = Math.max(...rows.map((r) => Number(r.tinggi_cm) || 0), 1);
  const stepX = plotW / rows.length;
  const barW = Math.max(14, Math.min(32, stepX * 0.5));
  const toYWeight = (v) => h - pad.bottom - (Number(v) / maxW) * plotH;
  const toYHeight = (v) => h - pad.bottom - (Number(v) / maxH) * plotH;

  ctx.fillStyle = '#0284c7';
  rows.forEach((item, idx) => {
    const x = pad.left + idx * stepX + stepX / 2 - barW / 2;
    const y = toYWeight(item.berat_kg);
    ctx.fillRect(x, y, barW, h - pad.bottom - y);
  });

  ctx.strokeStyle = '#16a34a';
  ctx.lineWidth = 2;
  ctx.beginPath();
  rows.forEach((item, idx) => {
    const x = pad.left + idx * stepX + stepX / 2;
    const y = toYHeight(item.tinggi_cm);
    if (idx === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  });
  ctx.stroke();

  ctx.fillStyle = '#16a34a';
  rows.forEach((item, idx) => {
    const x = pad.left + idx * stepX + stepX / 2;
    const y = toYHeight(item.tinggi_cm);
    ctx.beginPath();
    ctx.arc(x, y, 3, 0, Math.PI * 2);
    ctx.fill();
  });
}

async function renderPertumbuhanTable(cfg, tbody, rows) {
  const balitaRows = await fetchList('balita');
  const balitaMap = new Map(
    balitaRows.map((b) => [String(b.nama || '').trim().toLowerCase(), b])
  );

  tbody.innerHTML = '';
  if (!rows.length) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = 11;
    td.textContent = 'Belum ada data pertumbuhan.';
    tr.appendChild(td);
    tbody.appendChild(tr);
    await renderGrowthChart(rows);
    return;
  }

  rows.forEach((row, index) => {
    const tr = document.createElement('tr');

    const name = row.nama_balita ?? '';
    const profile = balitaMap.get(String(name).trim().toLowerCase()) || null;
    const ageMonths = profile ? toMonthAge(profile.tanggal_lahir, row.tanggal) : null;
    const weightRange = profile ? getWeightRange(ageMonths, profile.jenis_kelamin) : null;
    const heightRange = profile ? getHeightRange(ageMonths) : null;
    const weightStatus = classifyByRange(row.berat_kg, weightRange);
    const heightStatus = classifyByRange(row.tinggi_cm, heightRange);
    const finalStatus = (weightStatus.ok === true && heightStatus.ok === true)
      ? { text: 'Normal', cls: 'ok' }
      : { text: 'Tidak Normal', cls: 'bad' };
    const feedback = buildGrowthFeedback(weightStatus, heightStatus, row.berat_kg, row.tinggi_cm, weightRange, heightRange);

    const cells = [
      index + 1,
      name,
      row.berat_kg ?? '',
      row.tinggi_cm ?? '',
      fmtDate(row.tanggal)
    ];

    cells.forEach((v) => {
      const td = document.createElement('td');
      td.textContent = v;
      tr.appendChild(td);
    });

    const bbTd = document.createElement('td');
    bbTd.appendChild(createStatusBadge(weightStatus));
    tr.appendChild(bbTd);

    const tbTd = document.createElement('td');
    tbTd.appendChild(createStatusBadge(heightStatus));
    tr.appendChild(tbTd);

    const finalTd = document.createElement('td');
    finalTd.appendChild(createStatusBadge(finalStatus));
    tr.appendChild(finalTd);

    const detailTd = document.createElement('td');
    detailTd.className = 'growth-detail';
    detailTd.textContent = feedback;
    tr.appendChild(detailTd);

    const inputByTd = document.createElement('td');
    inputByTd.textContent = row.input_oleh || '-';
    tr.appendChild(inputByTd);

    const actionTd = document.createElement('td');
    actionTd.className = 'actions';
    if (CAN_EDIT) {
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-edit small';
      editBtn.textContent = 'Edit';
      editBtn.addEventListener('click', () => editItem('pertumbuhan', row));

      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'btn-delete small';
      deleteBtn.textContent = 'Hapus';
      deleteBtn.addEventListener('click', () => deleteItem('pertumbuhan', row[cfg.pk]));

      actionTd.append(editBtn, deleteBtn);
    } else {
      actionTd.textContent = '-';
    }
    tr.appendChild(actionTd);

    tbody.appendChild(tr);
  });

  await renderGrowthChart(rows);
}

async function renderEntity(entity) {
  const cfg = ENTITY_CONFIG[entity];
  if (!cfg) return;

  const tbody = document.getElementById(cfg.tbodyId);
  if (!tbody) return;

  const rows = await fetchList(entity);

  if (entity === 'pertumbuhan') {
    await renderPertumbuhanTable(cfg, tbody, rows);
    return;
  }

  tbody.innerHTML = '';

  rows.forEach((row, index) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${index + 1}</td>`;

    cfg.columns.forEach((col) => {
      const td = document.createElement('td');
      const value = /tanggal/i.test(col) ? fmtDate(row[col]) : row[col];
      td.textContent = value ?? '';
      tr.appendChild(td);
    });

    const inputByTd = document.createElement('td');
    inputByTd.textContent = row.input_oleh || '-';
    tr.appendChild(inputByTd);

    const actionTd = document.createElement('td');
    actionTd.className = 'actions';

    if (CAN_EDIT) {
      const editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn-edit small';
      editBtn.textContent = 'Edit';
      editBtn.addEventListener('click', () => editItem(entity, row));

      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'btn-delete small';
      deleteBtn.textContent = 'Hapus';
      deleteBtn.addEventListener('click', () => deleteItem(entity, row[cfg.pk]));

      actionTd.append(editBtn, deleteBtn);
    } else {
      actionTd.textContent = '-';
    }

    tr.appendChild(actionTd);
    tbody.appendChild(tr);
  });
}

async function deleteItem(entity, id) {
  if (!CAN_EDIT) {
    alert('Mode pengunjung hanya bisa melihat data.');
    return;
  }

  if (!confirm('Yakin ingin menghapus data ini?')) return;

  try {
    await apiRequest(entity, 'delete', 'DELETE', { id });
    await renderEntity(entity);
  } catch (error) {
    alert(error.message);
  }
}

function getModalFieldType(field) {
  if (/tanggal/i.test(field)) return 'date';
  if (/(berat|tinggi|usia|minggu)/i.test(field)) return 'number';
  return 'text';
}

function ensureEditModal() {
  let modal = document.getElementById('editModal');
  if (modal) {
    return {
      modal,
      title: document.getElementById('editModalTitle'),
      form: document.getElementById('editModalForm'),
      saveBtn: document.getElementById('editModalSave'),
      cancelBtn: document.getElementById('editModalCancel')
    };
  }

  modal = document.createElement('div');
  modal.id = 'editModal';
  modal.className = 'modal';
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="modal-content">
      <h3 id="editModalTitle">Edit Data</h3>
      <form id="editModalForm"></form>
      <div>
        <button type="button" id="editModalSave">Simpan</button>
        <button type="button" id="editModalCancel">Batal</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  return {
    modal,
    title: document.getElementById('editModalTitle'),
    form: document.getElementById('editModalForm'),
    saveBtn: document.getElementById('editModalSave'),
    cancelBtn: document.getElementById('editModalCancel')
  };
}

function initEditModal() {
  ensureEditModal();
}

function openEditModal(entity, cfg, row) {
  const modalEls = ensureEditModal();
  const { modal, title, form, saveBtn, cancelBtn } = modalEls;

  if (!modal || !title || !form || !saveBtn || !cancelBtn) {
    return Promise.resolve(null);
  }

  title.textContent = `Edit Data ${entity.charAt(0).toUpperCase()}${entity.slice(1)}`;
  form.innerHTML = '';

  cfg.columns.forEach((field) => {
    const label = document.createElement('label');
    label.textContent = cfg.labels[field] || field;

    const input = document.createElement('input');
    input.name = field;
    input.type = getModalFieldType(field);
    if (input.type === 'number') input.step = '0.1';

    const current = row[field] ?? '';
    input.value = input.type === 'date' ? String(current).slice(0, 10) : current;
    input.required = true;

    form.append(label, input);
  });

  modal.style.display = 'flex';
  modal.setAttribute('aria-hidden', 'false');

  return new Promise((resolve) => {
    const close = (payload) => {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      saveBtn.removeEventListener('click', onSave);
      cancelBtn.removeEventListener('click', onCancel);
      form.removeEventListener('submit', onSubmit);
      modal.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onEsc);
      resolve(payload);
    };

    const onSave = () => {
      if (!form.reportValidity()) return;
      const payload = {};
      cfg.columns.forEach((field) => {
        const input = form.elements.namedItem(field);
        payload[field] = input ? String(input.value).trim() : '';
      });
      close(payload);
    };

    const onCancel = () => close(null);
    const onSubmit = (event) => {
      event.preventDefault();
      onSave();
    };
    const onBackdrop = (event) => {
      if (event.target === modal) close(null);
    };
    const onEsc = (event) => {
      if (event.key === 'Escape') close(null);
    };

    saveBtn.addEventListener('click', onSave);
    cancelBtn.addEventListener('click', onCancel);
    form.addEventListener('submit', onSubmit);
    modal.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onEsc);

    const firstInput = form.querySelector('input, select, textarea');
    if (firstInput) firstInput.focus();
  });
}

async function editItem(entity, row) {
  if (!CAN_EDIT) {
    alert('Mode pengunjung hanya bisa melihat data.');
    return;
  }

  const cfg = ENTITY_CONFIG[entity];
  const payload = await openEditModal(entity, cfg, row);
  if (payload === null) return;

  try {
    await apiRequest(entity, 'update', 'PUT', {
      id: row[cfg.pk],
      data: payload
    });
    await renderEntity(entity);
    alert('Data berhasil diperbarui');
  } catch (error) {
    alert(error.message);
  }
}

async function initEntityForm(entity) {
  const cfg = ENTITY_CONFIG[entity];
  if (!cfg) return;

  await renderEntity(entity);

  const form = document.getElementById(cfg.formId);
  if (!form) return;

  const canCreate = CAN_CREATE_ENTITIES.includes(entity);

  if (!canCreate) {
    form.querySelectorAll('input, select, textarea, button').forEach((el) => {
      el.disabled = true;
    });

    const note = document.createElement('p');
    note.style.marginTop = '10px';
    note.style.fontSize = '13px';
    note.style.color = '#6d737a';
    note.textContent = 'Akun ini hanya bisa melihat data pada halaman ini.';
    form.appendChild(note);
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      const data = cfg.fromForm(form);
      await apiRequest(entity, 'create', 'POST', { data });
      form.reset();
      await renderEntity(entity);
      alert('Data berhasil disimpan');
    } catch (error) {
      alert(error.message);
    }
  });
}

async function initIndex() {
  const [balita, ibu, imunisasi, jadwal] = await Promise.all([
    fetchList('balita'),
    fetchList('ibu'),
    fetchList('imunisasi'),
    fetchList('jadwal')
  ]);

  const cardBalita = document.getElementById('cardBalita');
  const cardIbu = document.getElementById('cardIbu');
  const cardImun = document.getElementById('cardImun');
  const nextJadwal = document.getElementById('nextJadwal');

  if (cardBalita) cardBalita.textContent = balita.length;
  if (cardIbu) cardIbu.textContent = ibu.length;
  if (cardImun) cardImun.textContent = imunisasi.length;

  const nearest = [...jadwal]
    .filter((item) => item.tanggal)
    .sort((a, b) => new Date(a.tanggal) - new Date(b.tanggal))[0];

  if (nextJadwal) {
    nextJadwal.textContent = nearest
      ? `${fmtDate(nearest.tanggal)} - ${nearest.tempat}`
      : 'Belum ada jadwal';
  }
}

async function initReport() {
  const box = document.getElementById('laporanBox');
  if (!box) return;

  const [balita, ibu, imunisasi, pertumbuhan, jadwal] = await Promise.all([
    fetchList('balita'),
    fetchList('ibu'),
    fetchList('imunisasi'),
    fetchList('pertumbuhan'),
    fetchList('jadwal')
  ]);

  box.innerHTML = `
    <h3>Rekap Laporan Posyandu</h3>
    <p><b>Jumlah Balita:</b> ${balita.length}</p>
    <p><b>Jumlah Ibu Hamil:</b> ${ibu.length}</p>
    <p><b>Total Imunisasi:</b> ${imunisasi.length}</p>
    <p><b>Total Pengukuran Pertumbuhan:</b> ${pertumbuhan.length}</p>
    <p><b>Total Jadwal:</b> ${jadwal.length}</p>
    <button onclick="window.print()">Cetak Laporan</button>
  `;
}

async function refresh() {
  const page = document.body.getAttribute('data-page');

  try {
    if (page === 'index') await initIndex();
    if (page === 'balita') await initEntityForm('balita');
    if (page === 'ibu') await initEntityForm('ibu');
    if (page === 'imunisasi') await initEntityForm('imunisasi');
    if (page === 'pertumbuhan') await initEntityForm('pertumbuhan');
    if (page === 'jadwal') await initEntityForm('jadwal');
    if (page === 'laporan') await initReport();
  } catch (error) {
    console.error(error);
    alert(`Gagal memuat data: ${error.message}`);
  }
}

document.addEventListener('DOMContentLoaded', refresh);
document.addEventListener('DOMContentLoaded', initEditModal);

