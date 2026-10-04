export const iconOptions = [
    { value: 'Github', label: 'GitHub' },
    { value: 'Linkedin', label: 'LinkedIn' },
    { value: 'Instagram', label: 'Instagram' },
    { value: 'Youtube', label: 'YouTube' },
    { value: 'Dribbble', label: 'Dribbble' },
    { value: 'Figma', label: 'Figma' },
    { value: 'Mail', label: 'Email' },
    { value: 'Phone', label: 'Telepon' },
    { value: 'Globe', label: 'Situs' },
    { value: 'Link', label: 'Tautan' },
];

export const navigation = [
    { to: '/admin', label: 'Ringkasan', icon: 'LayoutDashboard', exact: true },
    { to: '/admin/profile', label: 'Profil', icon: 'User' },
    { to: '/admin/sections', label: 'Bagian', icon: 'Layers' },
    { to: '/admin/social-links', label: 'Tautan', icon: 'Link' },
    { to: '/admin/experiences', label: 'Pengalaman', icon: 'Briefcase' },
    { to: '/admin/educations', label: 'Pendidikan', icon: 'GraduationCap' },
    { to: '/admin/skills', label: 'Keahlian', icon: 'Sparkles' },
    { to: '/admin/projects', label: 'Proyek', icon: 'FolderKanban' },
    { to: '/admin/testimonials', label: 'Testimoni', icon: 'Quote' },
];

export const resources = {
    'social-links': {
        title: 'Tautan sosial',
        singular: 'tautan',
        heading: (row) => row.label,
        meta: (row) => row.url,
        fields: [
            { key: 'label', label: 'Label', type: 'text' },
            { key: 'icon', label: 'Ikon', type: 'icon' },
            { key: 'url', label: 'URL', type: 'text' },
        ],
    },
    experiences: {
        title: 'Pengalaman',
        singular: 'pengalaman',
        heading: (row) => row.role,
        meta: (row) => row.company,
        fields: [
            { key: 'role', label: 'Peran', type: 'text' },
            { key: 'company', label: 'Perusahaan', type: 'text' },
            { key: 'location', label: 'Lokasi', type: 'text' },
            { key: 'start_date', label: 'Mulai', type: 'month' },
            { key: 'end_date', label: 'Selesai', type: 'month' },
            { key: 'is_current', label: 'Masih berlangsung', type: 'checkbox' },
            { key: 'description', label: 'Deskripsi', type: 'textarea' },
        ],
    },
    educations: {
        title: 'Pendidikan',
        singular: 'pendidikan',
        heading: (row) => row.degree,
        meta: (row) => row.school,
        fields: [
            { key: 'school', label: 'Institusi', type: 'text' },
            { key: 'degree', label: 'Gelar', type: 'text' },
            { key: 'field', label: 'Bidang', type: 'text' },
            { key: 'start_year', label: 'Tahun mulai', type: 'number' },
            { key: 'end_year', label: 'Tahun selesai', type: 'number' },
            { key: 'description', label: 'Deskripsi', type: 'textarea' },
        ],
    },
    skills: {
        title: 'Keahlian',
        singular: 'keahlian',
        heading: (row) => row.name,
        meta: (row) => `${row.level} · ${row.category || 'Umum'}`,
        fields: [
            { key: 'name', label: 'Nama', type: 'text' },
            { key: 'category', label: 'Kategori', type: 'text' },
            { key: 'level', label: 'Tingkat (0–100)', type: 'number' },
        ],
    },
    projects: {
        title: 'Proyek',
        singular: 'proyek',
        heading: (row) => row.title,
        meta: (row) => (row.tech_stack || []).join(', '),
        fields: [
            { key: 'title', label: 'Judul', type: 'text' },
            { key: 'summary', label: 'Ringkasan', type: 'textarea' },
            { key: 'url', label: 'Tautan', type: 'text' },
            { key: 'tech_stack', label: 'Teknologi, dipisah koma', type: 'text' },
            { key: 'cover', label: 'Sampul', type: 'file', accept: 'image/jpeg,image/png,image/webp' },
        ],
    },
    testimonials: {
        title: 'Testimoni',
        singular: 'testimoni',
        heading: (row) => row.name,
        meta: (row) => [row.role, row.company].filter(Boolean).join(' · '),
        fields: [
            { key: 'name', label: 'Nama', type: 'text' },
            { key: 'role', label: 'Peran', type: 'text' },
            { key: 'company', label: 'Perusahaan', type: 'text' },
            { key: 'quote', label: 'Kutipan', type: 'textarea' },
            { key: 'avatar', label: 'Avatar', type: 'file', accept: 'image/jpeg,image/png,image/webp' },
        ],
    },
};
