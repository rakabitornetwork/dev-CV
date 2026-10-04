<script setup>
import {
    Briefcase,
    Eye,
    EyeOff,
    FolderKanban,
    GraduationCap,
    Layers,
    LayoutDashboard,
    Link,
    LogOut,
    Plus,
    Quote,
    Sparkles,
    Trash2,
    User,
    ChevronDown,
    ChevronUp,
    Save,
    FileText,
} from 'lucide-vue-next';

const icons = {
    Briefcase,
    Eye,
    EyeOff,
    FolderKanban,
    GraduationCap,
    Layers,
    LayoutDashboard,
    Link,
    LogOut,
    Plus,
    Quote,
    Sparkles,
    Trash2,
    User,
    ChevronDown,
    ChevronUp,
    Save,
    FileText,
};

defineProps({
    name: { type: String, default: 'Link' },
    size: { type: String, default: 'size-4' },
});
</script>

<template>
    <component :is="icons[name] || icons.Link" :stroke-width="1.5" :class="size" aria-hidden="true" />
</template>
