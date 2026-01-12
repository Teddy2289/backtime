<template>
    <div class="modal-backdrop" @click.self="close">
        <div class="modal">
            <div class="modal-header">
                <h3>Task Details - {{ task.title }}</h3>
                <button class="close-btn" @click="close">&times;</button>
            </div>
            <div class="modal-content">
                <p><strong>Description:</strong> {{ task.description }}</p>
                <p><strong>Status:</strong> <span :class="`status-${task.status}`">{{ task.status }}</span></p>
                <p><strong>Priority:</strong> <span :class="`priority-${task.priority}`">{{ task.priority }}</span></p>
                <p><strong>Due Date:</strong> {{ formatDate(task.due_date) }}</p>
                <p><strong>Progress:</strong> {{ task.progress }}%</p>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'TaskDetailModal',
    props: {
        task: {
            type: Object,
            required: true
        }
    },
    methods: {
        close() {
            this.$emit('close');
        },
        formatDate(date) {
            if (!date) return 'N/A';
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(date).toLocaleDateString(undefined, options);
        }
    }
};

</script>