<template>
    <div class="content">
        <div class="content__main-view">
            <div class="Technician">
                <PersonForm
                    :person="person"
                    :errors="errors"
                    @submit="saveTechnician"
                    @change="handleFormChange"
                    @cancel="flushStashedData"
                />
                <Help :message="help" :error="error" :isLoading="isLoading" />
            </div>
        </div>
    </div>
</template>

<script src="./index.js"></script>
