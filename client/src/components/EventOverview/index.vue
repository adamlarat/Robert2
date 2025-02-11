<template>
    <div class="EventOverview">
        <h1 class="EventOverview__title">{{ event.title }}</h1>
        <div class="EventOverview__header">
            <section class="EventOverview__section">
                <h2 class="EventOverview__dates-location">
                    <i class="fas fa-map-marker-alt" />
                    <span v-if="event.location"> {{ $t('in') }} {{ event.location }}, </span>
                    {{ $t('from-date-to-date', fromToDates) }}
                </h2>
            </section>
            <section class="EventOverview__section">
                <h2 class="EventOverview__duration">
                    <i class="far fa-clock" />
                    {{ $t('duration') }} {{ $t('days-count', { duration }, duration) }}
                </h2>
            </section>
        </div>
        <p v-if="event.description" class="EventOverview__description">
            <i class="fas fa-clipboard" />
            {{ event.description }}
        </p>
        <div class="EventOverview__main">
            <section v-if="event.beneficiaries.length > 0" class="EventOverview__section">
                <dl class="EventOverview__info EventOverview__info--vertical">
                    <dt class="EventOverview__info__term">
                        <i class="fas fa-address-book" />
                        {{ $t('page-events.event-beneficiaries') }}
                    </dt>
                    <dd class="EventOverview__info__value">
                        <ul class="EventOverview__info__list">
                            <li
                                v-for="beneficiary in event.beneficiaries"
                                :key="beneficiary.id"
                                class="EventOverview__info__list-item"
                            >
                                <router-link
                                    :to="`/beneficiaries/${beneficiary.id}`"
                                    :title="$t('action-edit')"
                                >
                                    {{ beneficiary.full_name }}
                                </router-link>
                                <router-link
                                    v-if="beneficiary.company"
                                    :to="`/companies/${beneficiary.company_id}`"
                                    :title="$t('action-edit')"
                                >
                                    ({{ beneficiary.company.legal_name }})
                                </router-link>
                            </li>
                        </ul>
                    </dd>
                </dl>
            </section>
            <section v-if="event.technicians.length > 0" class="EventOverview__section">
                <dl class="EventOverview__info EventOverview__info--vertical">
                    <dt class="EventOverview__info__term">
                        <i class="fas fa-people-carry" />
                        {{ $t('page-events.event-technicians') }}
                    </dt>
                    <dd class="EventOverview__info__value">
                        <ul class="EventOverview__info__list">
                            <li
                                v-for="technician in technicians"
                                :key="technician.id"
                                class="EventOverview__info__list-item"
                            >
                                <router-link
                                    :key="technician.id"
                                    :to="`/technicians/${technician.id}/view#infos`"
                                    class="EventOverview__info__link"
                                    :title="$t('action-edit')"
                                >
                                    {{ technician.name }}
                                </router-link>
                                <span v-if="technician.phone">− {{ technician.phone }}</span>
                                <br />
                                <ul class="EventOverview__technician-periods">
                                    <li
                                        v-for="period in technician.periods"
                                        :key="period.id"
                                        class="EventOverview__technician-periods__item"
                                    >
                                        {{ period.from.format('DD MMM LT') }} ⇒
                                        {{ period.to.format('DD MMM LT') }} :
                                        {{ period.position }}
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </dd>
                </dl>
            </section>
        </div>
        <div class="EventOverview__materials">
            <h3 class="EventOverview__materials__title">
                <i class="fas fa-box" />
                {{ $t('page-events.event-materials') }}
            </h3>
            <EventMaterials
                v-if="hasMaterials"
                :event="event"
                :withRentalPrices="showBilling && event.is_billable"
                :hideDetails="showBilling && event.is_billable"
            />
        </div>
        <h3 v-if="showBilling && event.is_billable" class="EventOverview__billing-title">
            <i class="fas fa-file-invoice-dollar" />
            {{ $t('billing') }}
        </h3>
        <div class="EventOverview__billing">
            <EventTotals
                v-if="hasMaterials"
                :event="event"
                :withRentalPrices="showBilling && event.is_billable"
                :forcedDiscountRate="discountRate"
            />
            <tabs
                v-if="showBilling && hasMaterials && event.is_billable"
                :defaultIndex="hasBill ? 1 : 0"
                :onSelect="handleChangeBillingTab"
                class="EventOverview__billing__tabs"
            >
                <tab titleSlot="estimates">
                    <EventEstimates
                        :event="event"
                        :loading="isCreating"
                        :deletingId="deletingId"
                        @discountRateChange="handleChangeDiscountRate"
                        @createEstimate="handleCreateEstimate"
                        @deleteEstimate="handleDeleteEstimate"
                    />
                    <Help :message="{ type: 'success', text: successMessage }" :error="error" />
                </tab>
                <tab titleSlot="bill">
                    <Help :message="{ type: 'success', text: successMessage }" :error="error" />
                    <EventBilling
                        :event="event"
                        :loading="isCreating"
                        @discountRateChange="handleChangeDiscountRate"
                        @createBill="handleCreateBill"
                    />
                </tab>
                <template slot="estimates">
                    <i class="fas fa-file-signature" /> {{ $t('estimates') }}
                </template>
                <template slot="bill">
                    <i class="fas fa-file-invoice-dollar" /> {{ $t('bill') }}
                </template>
            </tabs>
            <p v-if="!hasMaterials" class="EventOverview__materials__empty">
                <i class="fas fa-exclamation-triangle" />
                {{ $t('page-events.warning-no-material') }}
            </p>
        </div>
        <div class="EventOverview__missing-materials">
            <EventMissingMaterials :eventId="event.id" />
        </div>
    </div>
</template>

<script src="./index.js"></script>
