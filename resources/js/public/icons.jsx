import {
    ArrowUpRight,
    Briefcase,
    Download,
    Dribbble,
    Figma,
    Folder,
    Github,
    Globe,
    GraduationCap,
    Instagram,
    Link,
    Linkedin,
    Mail,
    MapPin,
    Menu,
    Moon,
    Phone,
    Quote,
    Sparkles,
    Sun,
    User,
    X,
    Youtube,
} from 'lucide-react';

const icons = {
    Github,
    Linkedin,
    Instagram,
    Youtube,
    Dribbble,
    Figma,
    Folder,
    Mail,
    Phone,
    Globe,
    Link,
    MapPin,
    Briefcase,
    GraduationCap,
    Sparkles,
    Quote,
    User,
    Download,
    ArrowUpRight,
    Moon,
    Sun,
    Menu,
    X,
};

export function CvIcon({ name, className = 'size-5', ...props }) {
    const Icon = icons[name] || Link;

    return <Icon strokeWidth={1.5} className={className} aria-hidden="true" {...props} />;
}

export const sectionIcons = {
    about: 'User',
    experience: 'Briefcase',
    education: 'GraduationCap',
    skills: 'Sparkles',
    projects: 'Folder',
    testimonials: 'Quote',
    contact: 'Mail',
};

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

export function formatMonth(value) {
    if (!value) {
        return '';
    }

    const [year, month] = value.split('-');

    return `${months[Number(month) - 1] || ''} ${year}`.trim();
}
