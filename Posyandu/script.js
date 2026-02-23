const API_URL = 'api.php';
const USER_ROLE = document.body?.dataset.role || 'public';
const CAN_EDIT = USER_ROLE === 'admin';
const CAN_CREATE_ENTITIES = USER_ROLE === 'admin'
  ? ['balita', 'ibu', 'imunisasi', 'pertumbuhan', 'jadwal']
  : USER_ROLE === 'user'
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

async function renderEntity(entity) {
  const cfg = ENTITY_CONFIG[entity];
  if (!cfg) return;

  const tbody = document.getElementById(cfg.tbodyId);
  if (!tbody) return;

  const rows = await fetchList(entity);
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

async function editItem(entity, row) {
  if (!CAN_EDIT) {
    alert('Mode pengunjung hanya bisa melihat data.');
    return;
  }

  const cfg = ENTITY_CONFIG[entity];
  const payload = {};

  for (const field of cfg.columns) {
    const label = cfg.labels[field] || field;
    const currentValue = row[field] ?? '';
    const nextValue = prompt(`Ubah ${label}:`, currentValue);
    if (nextValue === null) return;
    payload[field] = nextValue;
  }

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
