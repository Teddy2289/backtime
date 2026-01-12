<!-- src/components/tasks/UserAssignModal.vue -->
<template>
    <div class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h2>Assign to User</h2>
                <button @click="$emit('close')" class="btn-close">×</button>
            </div>

            <div class="search-section">
                <input v-model="searchQuery" type="text" placeholder="Search users..." class="search-input" />
            </div>

            <div class="users-list">
                <div v-for="user in filteredUsers" :key="user.id" class="user-item"
                    :class="{ 'selected': selectedUser?.id === user.id }" @click="selectUser(user)">
                    <div class="user-avatar">
                        <span v-if="user.avatar">
                            <img :src="user.avatar" :alt="user.name" />
                        </span>
                        <span v-else class="avatar-fallback">
                            {{ user.initials || getInitials(user.name) }}
                        </span>
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ user.name }}</span>
                        <span class="user-email">{{ user.email }}</span>
                    </div>
                    <div class="user-select">
                        <div v-if="selectedUser?.id === user.id" class="selected-icon">
                            ✓
                        </div>
                    </div>
                </div>

                <div v-if="filteredUsers.length === 0" class="no-users">
                    No users found
                </div>
            </div>

            <div class="modal-actions">
                <button @click="$emit('close')" class="btn btn-secondary">
                    Cancel
                </button>
                <button @click="assign" class="btn btn-primary" :disabled="!selectedUser">
                    Assign
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { taskService } from '@/services/task.service';

interface Props {
    projectId: number;
}

interface Emits {
    (e: 'close'): void;
    (e: 'assign', userId: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const users = ref<any[]>([]);
const selectedUser = ref<any>(null);
const searchQuery = ref('');
const loading = ref(false);

onMounted(async () => {
    await loadUsers();
});

const loadUsers = async () => {
    try {
        loading.value = true;
        users.value = await taskService.getAssignableUsers(props.projectId);
    } catch (err) {
        console.error('Failed to load users:', err);
    } finally {
        loading.value = false;
    }
};

const filteredUsers = computed(() => {
    if (!searchQuery.value.trim()) {
        return users.value;
    }

    const query = searchQuery.value.toLowerCase();
    return users.value.filter(user =>
        user.name.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query)
    );
});

const selectUser = (user: any) => {
    selectedUser.value = user;
};

const assign = () => {
    if (selectedUser.value) {
        emit('assign', selectedUser.value.id);
    }
};

const closeModal = (e: MouseEvent) => {
    if ((e.target as HTMLElement).classList.contains('modal-overlay')) {
        emit('close');
    }
};

const getInitials = (name: string) => {
    if (!name) return '??';
    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
}

.search-section {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.search-input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
}

.search-input:focus {
    outline: none;
    border-color: #2196f3;
}

.users-list {
    flex: 1;
    overflow-y: auto;
    max-height: 400px;
}

.user-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    cursor: pointer;
    transition: background 0.2s;
}

.user-item:hover {
    background: #f5f5f5;
}

.user-item.selected {
    background: #e3f2fd;
}

.user-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #666;
    margin-right: 12px;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.user-info {
    flex: 1;
}

.user-name {
    display: block;
    font-weight: 500;
    color: #333;
}

.user-email {
    display: block;
    font-size: 12px;
    color: #666;
}

.user-select {
    width: 24px;
    height: 24px;
    border: 2px solid #e0e0e0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.selected-icon {
    color: #2196f3;
    font-weight: bold;
}

.no-users {
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-style: italic;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #e0e0e0;
}
</style>