<template>
    <div class="content">
        <div class="content__main-view">
            <div class="Beneficiary">
                <PersonForm
                    :person="person"
                    :errors="errors"
                    @submit="saveBeneficiary"
                    @change="handleFormChange"
                    @cancel="flushStashedData"
                    withCompany
                    withReference
                />
                <Help :message="help" :error="error" :isLoading="isLoading" />
            </div>
        </div>
    </div>
</template>

<script src="./index.js"></script>
